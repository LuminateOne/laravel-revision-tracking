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
     */
    public function testUpdate()
    {
        $faker = \Faker\Factory::create();

        //Get the Model name and columns
        $dataProvider = $this->modelProvider()[2];
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];

        // Create a new Model
        $model = new $modelName();

        //Create a record
        $record = $model->create($columns);

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
    }
}
