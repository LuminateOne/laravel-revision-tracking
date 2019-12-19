<?php

namespace LuminateOne\RevisionTracking\Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LuminateOne\RevisionTracking\RevisionTracking;

class RevisionTestAll extends TestCase
{
    use RefreshDatabase;

    /**
     * Change the revision mode to single
     */
    public function setUp(): void
    {
        parent::setUp();

        config(['revision_tracking.mode' => 'all']);
    }

    /**
     * Test if the updated event can be caught by Revisionable.
     * It will check if a new revision is created after update the Model
     * It will check if the original_values stored in the revision table are equals to the old Model
     *
     * @throws \ErrorException If the Model does not have a primary key
     */
    public function testUpdate()
    {
        $faker = \Faker\Factory::create();

        $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
        $record = $this->setupModel($modelName);
        $oldRecord = clone $record;

        foreach (($record->getFillable()) as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();

        $aRevision = $record->allRevisions()->latest('id')->first();

        $modelIdentifiers = [$record->getKeyName() => $record->getKey()];

        // Check if the revision identifier are equal
        $this->assertEquals($modelIdentifiers, $aRevision->revision_identifier,
            'The identifiers of revision and the primary key of the Model should match');

        // Check if the values stored in the revision table equals to the old record
        $hasDifferent = true;
        foreach ($aRevision->original_values as $value) {
            if ($oldRecord[$value['column']] !== $value['value']) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertTrue($hasDifferent, "Attribute values of revisiopn and the old Model should match");

        return $record;
    }

    /**
     * Test get all revisions
     * This will create and updated two different Models,
     * So it can test if the "allRevision" method only returns
     * the revisions that belong to the current Model
     *
     * @throws \ErrorException  If the Model does not have a primary key
     *                          If the Model does not have any revision
     */
    public function testGetAllRevision()
    {
        $faker = \Faker\Factory::create();

        $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
        $record = $this->setupModel($modelName);
        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        // Create and updated a different Model
        $modelName2 = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
        $record2 = $this->setupModel($modelName2);
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record2->getFillable()) as $key) {
                $record2[$key] = $faker->name;
            }
            $record->save();
        }

        $revisionCount = $record->allRevisions()->get()->count();

        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $this->assertEquals($updateCount, $revisionCount, "Revision count should be " . $updateCount);
        } else {
            $this->assertEquals(1, $revisionCount, "Revision count should be 1");
        }
    }

    /**
     * Test get a single revision by revision ID
     * This will create and update a model,
     * Then get the latest revision, and check if the identifiers are the same
     *
     * @throws \ErrorException  If the Model does not have a primary key
     *                          If the Model does not have any revision
     */
    public function testGetRevision()
    {
        $faker = \Faker\Factory::create();

        $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
        $record = $this->setupModel($modelName);
        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        $latestRevisionId = $record->allRevisions()->latest('id')->first()->id;

        $singleRevision = $record->getRevision($latestRevisionId);

        $modelIdentifiers = [$record->getKeyName() => $record->getKey()];

        $this->assertEquals($modelIdentifiers, $singleRevision->revision_identifier, "Identifiers do not match");
    }

    /**
     * Test rollback, it will insert a new record, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     *
     * @throws \ErrorException  If the Model does not have a primary key
     *                          If the Model does not have any revision
     */
    public function testRollback()
    {
        $faker = \Faker\Factory::create();

        $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
        $record = $this->setupModel($modelName);
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        $aRevision = $record->allRevisions()->orderBy('id', 'asc')->first();

        \Log::info("testRollback");
        \Log::info("oldRecord");
        \Log::info(print_r($oldRecord->getAttributes(), true));
        \Log::info("aRevision");
        \Log::info(print_r($aRevision->getAttributes(), true));

        $record->rollback($aRevision->id, true);

        $restoredRecord = (new $modelName())->find($aRevision->revision_identifier)->first();

        \Log::info("restoredRecord");
        \Log::info(print_r($restoredRecord->getAttributes(), true));

        $hasDifferent = true;
        foreach ($record->getFillable() as $key) {
            if ($oldRecord[$key] !== $restoredRecord[$key]) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');
    }

    /**
     * Test rollback, it will insert a new record, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     *
     * @throws \ErrorException  If the Model does not have a primary key
     *                          If the Model does not have any revision
     */
    // public function testRollbackAndNotSaveAsRevision()
    // {
    //     $faker = \Faker\Factory::create();
    //
    //     $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
    //     $record = $this->setupModel($modelName);
    //     $oldRecord = clone $record;
    //
    //     $updateCount = 3;
    //     for ($i = 0; $i < $updateCount; $i++) {
    //         foreach (($record->getFillable()) as $key) {
    //             $record[$key] = $faker->name;
    //         }
    //         $record->save();
    //     }
    //
    //     $revisionId = $record->allRevisions()->orderBy('id', 'asc')->first()->id;
    //
    //     $latestRevision = $record->getRevision($revisionId);
    //
    //     $record->rollback($revisionId, false);
    //
    //     $restoredRecord = (new $modelName())->find($latestRevision->revision_identifier)->first();
    //
    //     $hasDifferent = true;
    //     foreach ($record->getFillable() as $key) {
    //         if ($oldRecord[$key] !== $restoredRecord[$key]) {
    //             $hasDifferent = false;
    //             break;
    //         }
    //     }
    //     $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');
    //
    //     $revisionCount = $record->allRevisions()->where([['id', '>=', $revisionId]])->count();
    //     $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    // }


    /**
     * Test if the revision will be deleted after deleting a Model
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testDelete()
    {
        $faker = \Faker\Factory::create();

        $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
        $record = $this->setupModel($modelName);

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        $record->delete();

        $revisionCount = $record->allRevisions()->count();

        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }

    /**
     *  Test the php artisan table:revision {modelName} command
     *  It will run the command and then check if the table exists in the database
     */
    public function testPHPCommand()
    {
        $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
        $model = $this->setupModel($modelName);
        exec('php artisan table:revision ' . $modelName, $output, $return_var);

        $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();

        $this->assertTrue(Schema::hasTable($revisionTableName),
            'The revision table should be created for the ' . $modelName);
    }

    /**
     * It will create a new Model
     * Since we are using RefreshDatabase Trait, so it will also create the table for the model
     * and the revision table will be created if the revision mode is set to single
     *
     * @param  string $modelName A model name with namespace
     * @return Model    Return the created model
     */
    private function setupModel($modelName)
    {
        $faker = \Faker\Factory::create();
        $model = new $modelName();
        $model->createTable();

        foreach (($model->getFillable()) as $key) {
            $model[$key] = $faker->name;
        }
        $model->save();

        if ($model->revisionMode() === 'single') {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();

            Schema::create($revisionTableName, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('revision_identifier');
                $table->text('original_values');
                $table->timestamps();
            });
        }

        return $model;
    }
}