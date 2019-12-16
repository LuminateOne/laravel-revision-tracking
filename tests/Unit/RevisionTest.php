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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Log;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the fake data for the Test Models
     * @return mixed
     */
    public function modelProvider($index)
    {
        $faker = \Faker\Factory::create();

        $models = [
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

        $modelName = $models[$index]['model'];
        $model = new $modelName();

        if($model->revisionMode() === 'single'){
            Schema::create(config('revision_tracking.table_prefix', 'revisions_') . $model->getTable(), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('revision_identifier');
                $table->text('original_values');
                $table->timestamps();
            });
        }

        return $models[$index];
    }

    public function testInsert($dataProvider = null)
    {
        //Get the Model name and columns
        if (!$dataProvider) {
            $dataProvider = $this->modelProvider()[0];
        }
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];

        // Create a new Model
        $model = new $modelName();

        //Create a record
        $record = $model->create($columns);

        $this->assertTrue($record !== null, "The record has not been inserted");

        return $record;
    }

    /**
     * Test if the updated event can be catched be Revisionable.
     *
     * @param null $record      The newly created model
     * @return $insertedRecord  Clone the created the model.
     */
    public function testUpdate($record = null)
    {
        $faker = \Faker\Factory::create();

        if (!$record) {
            $record = $this->testInsert($this->modelProvider(0));
        }

        foreach (($record->getFillable()) as $key) {
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

        return $record;
    }

    /**
     * Test get all revisions
     * This will create and updated two different Model,
     * So it can test if the "allRevision" method only return
     * the revisions that belongs to the current Model
     *
     * @throws \ErrorException
     */
    public function testGetAllRevision()
    {
        //Get the Model and the fake data
        $dataProvider = $this->modelProvider(0);

        $record = $this->testInsert($dataProvider);

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        // Create and updated a different Model
        $record2 = $this->testInsert($this->modelProvider()[1]);
        for ($i = 0; $i < $updateCount; $i++) {
           $this->testUpdate($record2);
        }

        // Restore the revision
        $allReivisons = $record->allRevisions()->get();

        $this->assertEquals($updateCount, count($allReivisons), "Revision count should be " . $updateCount);
    }

    /**
     * Test get revisions
     *
     * @throws \ErrorException
     */
    public function testGetRevision()
    {
        //Get the Model and the fake data
        $dataProvider = $this->modelProvider(0);
        $record = $this->testInsert($dataProvider);
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $singleRevision = $record->getRevision(1);

        $recordIds = [$oldRecord->getKeyName() => $oldRecord->getKey()];

        $this->assertEquals($recordIds, $singleRevision->revision_identifier, "Identifiers do not match");

        $hasDifferent = true;
        foreach ($singleRevision->original_values as $value) {
            if ($oldRecord[$value['column']] !== $value['value']) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertTrue($hasDifferent, "Attribute values do not match");
    }


    /**
     * Test rollback, it will insert a new recored, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     *
     * @throws \ErrorException
     */
    public function testRollback()
    {
        //Get the Model and the fake data
        $dataProvider = $this->modelProvider(0);
        $modelName = $dataProvider['model'];
        $model = new $modelName();

        $record = $this->testInsert($dataProvider);
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $saveAsRevision = false;
        $revisionId = 1;
        $record->rollback($revisionId, $saveAsRevision);

        $restoredRecord = $model->find($record->getKey())->first();

        $hasDifferent = true;
        foreach ($oldRecord->getFillable() as $key) {
            if ($oldRecord[$key] !== $restoredRecord[$key]) {
                $hasDifferent = false;
                break;
            }
        }

        $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');

        if(!$saveAsRevision){
            $revisionCount = $record->allRevisions()->where([['id', '>=', $revisionId]])->count();
            $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
        }
    }
}
