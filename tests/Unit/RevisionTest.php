<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\TableOneUnique;
use LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LuminateOne\RevisionTracking\RevisionTracking;
use Tests\TestCase;
use Faker\Generator as Faker;
use Log;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the fake data for the Test Models
     * @return mixed
     */
    public function modelProvider()
    {
        $faker = \Faker\Factory::create();

        return [
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey',
                'columns' => ['name' => $faker->name]
            ],
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey',
                'columns' => ['name1' => $faker->name, 'name2' => $faker->name]
            ],
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey',
                'columns' => ['name' => $faker->name]
            ],
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\TableOneUnique',
                'columns' => [
                    'name' => $faker->name,
                    'name1' => $faker->name,
                    'name2' => $faker->name,
                    'name3' => $faker->name,
                    'name4' => $faker->name,
                    'name5' => $faker->name
                ]
            ],
        ];
    }

    /**
     * Test if the updated event can be catched be Revisionable.
     *
     * @param null $dataProvider    The dataProvider may be passed from other function
     * @return $insertedRecord      Clone the created the record.
     */
    public function testUpdate($dataProvider = null)
    {
        $faker = \Faker\Factory::create();

        //Get the Model name and columns
        if(!$dataProvider){
            $dataProvider = $this->modelProvider()[0];
        }
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];

        // Create a new Model
        $model = new $modelName();

        //Create a record
        $record = $model->create($columns);
        $insertedRecord = clone $record;

        //Update the record
        foreach ($columns as $key => $value) {
            $record[$key] = $faker->name;
        }
        $record->save();

        //Get the changed value
        $originalValuesChanged = RevisionTracking::eloquentDiff($record);

        // Get the latest revision
        $revisionModel = $record->getRevisionModel();
        $aRevision = $revisionModel->latest('id')->first();

        $identifier = [$record->getKeyName() => $record->getKey()];

        // Check if the revision identifier are equal
        $this->assertEquals($identifier, $aRevision->revision_identifier, 'Identifiers do not match');

        return $insertedRecord;
    }


    /**
     * Test reviosn restore, it will insert a new recored, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     * @throws \ErrorException
     */
    public function testRestore()
    {
        //Get the Model and the fake data
        $dataProvider = $this->modelProvider()[0];
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];
        $model = new $modelName();

        // Insert and update the Model
        $oldRecord = $this->testUpdate($dataProvider);

        // Restore the revision
        RevisionTracking::eloquentRestore($modelName);

        $restoredRecord = $model->find($oldRecord->getKey())->first();

        $hasDifferent = true;
        foreach ($columns as $key => $value) {
            if ($value !== $restoredRecord->getAttributes()[$key]) {
                $hasDifferent = false;
                break;
            }
        }

        $this->assertEquals(true, $hasDifferent, 'Names does not match');
    }
}
