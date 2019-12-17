<?php

namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\TableOneUnique;
use LuminateOne\RevisionTracking\TestModels\customizedPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LuminateOne\RevisionTracking\RevisionTracking;
use Tests\TestCase;
use Faker\Generator as Faker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the fake data for the Test Models
     * @return mixed
     */
    public function modelProvider($index)
    {
        $models = [
            'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\TableOneUnique',
        ];

        $modelName = $models[$index];
        $model = new $modelName();

        if ($model->revisionMode() === 'single') {
            Schema::create(config('revision_tracking.table_prefix', 'revisions_') . $model->getTable(), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('revision_identifier');
                $table->text('original_values');
                $table->timestamps();
            });
        }

        return $model;
    }


    /**
     * Insert a new Model
     *
     * @param null $testModel
     * @return mixed
     */
    public function testInsert($testModel = null)
    {
        $faker = \Faker\Factory::create();

        //Get the Model name and columns
        if (!$testModel) {
            $testModel = $this->modelProvider(0);
        }

        // Create a new Model
        $record = new $testModel();

        foreach (($record->getFillable()) as $key) {
            $record[$key] = $faker->name;
        }

        //Create a record
        $record->save();

        $this->assertTrue($record !== null, "The record has not been inserted");

        return $record;
    }

    /**
     * Test if the updated event can be catched be Revisionable.
     *
     * @param null $record The newly created model
     * @return $insertedRecord  Clone the created the model.
     */
    public function testUpdate($record = null)
    {
        $faker = \Faker\Factory::create();

        if (!$record) {
            $record = $this->testInsert($this->modelProvider(3));
        }
        $oldRecord = clone $record;

        foreach (($record->getFillable()) as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();

        //Get the changed value
        $originalValuesChanged = RevisionTracking::eloquentDiff($record);

        // Get the latest revision
        $aRevision = $record->allRevisions()->latest('id')->first();

        // Check if the revision identifier are equal
        $this->assertEquals([$record->getKeyName() => $record->getKey()], $aRevision->revision_identifier,
            'Identifiers do not match');

        // The the values stored in the revision table
        $hasDifferent = true;
        foreach ($aRevision->original_values as $value) {
            if ($oldRecord[$value['column']] !== $value['value']) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertTrue($hasDifferent, "Attribute values do not match");

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
        //Get the Model
        $testModel = $this->modelProvider(0);

        $record = $this->testInsert($testModel);

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        // Create and updated a different Model
        $record2 = $this->testInsert($this->modelProvider(1));
        for ($i = 0; $i < $updateCount; $i++) {
            $this->testUpdate($record2);
        }

        // Restore the revision
        $allReivisons = $record->allRevisions()->get();

        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $this->assertEquals($updateCount, count($allReivisons), "Revision count should be " . $updateCount);
        } else {
            $this->assertEquals(1, count($allReivisons), "Revision count should be " . $updateCount);
        }
    }

    /**
     * Test get revisions
     * Test differently when user set a customized primary key
     *
     * @throws \ErrorException
     */
    public function testGetRevision()
    {
        //Get the Model
        $testModel = $this->modelProvider(0);
        $record = $this->testInsert($testModel);
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $revisionId = 1;
        //When user set the customized primary key
        if ($record->getKeyName() !== "id" || $record->incrementing !== true) {
            $revisionId = 3;
        }

        $singleRevision = $record->getRevision($revisionId);

        $this->assertEquals([$record->getKeyName() => $record->getKey()], $singleRevision->revision_identifier, "Identifiers do not match");

        // If the user did not set a customized primary key, then comppare the changed the fields
        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $hasDifferent = true;
            foreach ($singleRevision->original_values as $value) {
                if ($oldRecord[$value['column']] !== $value['value']) {
                    $hasDifferent = false;
                    break;
                }
            }
            $this->assertTrue($hasDifferent, "Attribute values do not match");
        }
    }

    /**
     * Test rollback, it will insert a new recored, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     *
     * @throws \ErrorException
     */
    public function testRollback()
    {
        //Get the Model
        $testModel = $this->modelProvider(3);

        $record = $this->testInsert($testModel);
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $saveAsRevision = true;

        $revisionId = 1;
        //Check if user set a customized primary key
        if ($record->getKeyName() !== "id" || $record->incrementing !== true) {
            $revisionId = 3;
        }
        $latestRevision = $record->getRevision($revisionId);

        $record->rollback($revisionId, $saveAsRevision);

        $restoredRecord = $testModel->find($latestRevision->revision_identifier)->first();

        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $hasDifferent = true;
            foreach ($oldRecord->getFillable() as $key) {
                if ($oldRecord[$key] !== $restoredRecord[$key]) {
                    $hasDifferent = false;
                    break;
                }
            }
            $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');
        }

        if (!$saveAsRevision) {
            $revisionCount = $record->allRevisions()->where([['id', '>=', $revisionId]])->count();
            $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
        }
    }


    /**
     * Test delete
     * Test will the revision be deleted after delete a Model
     *
     * @throws \ErrorException
     */
    public function testDelete()
    {
        //Get the Model
        $testModel = $this->modelProvider(1);

        $record = $this->testInsert($testModel);
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $record->delete();

        $revisionCount = $record->allRevisions()->count();

        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }
}
