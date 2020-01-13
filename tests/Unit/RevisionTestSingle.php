<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class RevisionTestSingle extends TestCase
{

    /**
     * Change the revision mode to single
     */
    public function setUp(): void
    {
        parent::setUp();

        config(['revision_tracking.mode' => 'single']);
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
            $record2->save();
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

        $record->rollback($aRevision->id, true);

        $hasDifferent = true;
        foreach ($record->getFillable() as $key) {
            if ($oldRecord[$key] !== $record[$key]) {
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
    public function testRollbackAndNotSaveAsRevision()
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

        $record->rollback($aRevision->id, false);

        $hasDifferent = true;
        foreach ($record->getFillable() as $key) {
            if ($oldRecord[$key] !== $record[$key]) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');

        $revisionCount = $record->allRevisions()->where([['id', '>=', $aRevision->id]])->count();
        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if the revision will be deleted after deleting a Model
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testRemoveOnDelete()
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

        config(['revision_tracking.remove_on_delete' => true]);

        $record->delete();

        $revisionCount = $record->allRevisions()->count();

        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if the revision will be deleted after deleting a Model
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testNotRemoveOnDelete()
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

        config(['revision_tracking.remove_on_delete' => false]);

        $record->delete();

        $revisionCount = $record->allRevisions()->count();

        $this->assertEquals($updateCount, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if an ErrorException will be thrown if a Model want to rollback to a revision which does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testNoPrimaryKeyException()
    {
        try {
            $faker = \Faker\Factory::create();

            $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
            $record = $this->setupModel($modelName);

            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();

            $record->rollback(10);

        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }

    /**
     * Test if an ErrorException will be thrown if the revision table does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testNoRevisionTableException()
    {
        try {
            $faker = \Faker\Factory::create();

            $modelName = 'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey';
            $record = $this->setupModel($modelName);

            Schema::drop($record->getRevisionModel()->getTable());

            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }

    /**
     * Test if an ErrorException will be thrown if a Model uses the Revisionable Trait without a primary key
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testNoRevisionException()
    {
        try {
            $faker = \Faker\Factory::create();

            $modelName = 'LuminateOne\RevisionTracking\Tests\Models\NoPrimaryKey';
            $record = $this->setupModel($modelName);

            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }
}