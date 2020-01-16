<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey;

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
     * Test if an ErrorException will be thrown if the revision table does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testNoRevisionTableException()
    {
        try {
            $faker = \Faker\Factory::create();

            $model = $this->setupModel(DefaultPrimaryKey::class);

            Schema::drop($model->getRevisionModel()->getTable());

            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
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

        $model = $this->setupModel(DefaultPrimaryKey::class);
        $oldModel = clone $model;

        foreach (($model->getFillable()) as $key) {
            $model[$key] = $faker->name;
        }
        $model->save();

        $aRevision = $model->allRevisions()->latest('id')->first();

        $modelIdentifiers = $model->modelIdentifier();

        // Check if the model identifier are equal
        $this->assertEquals($modelIdentifiers, $aRevision->model_identifier,
            'The identifiers of revision and the primary key of the Model should match');

        // Check if the values stored in the revision table equals to the old record
        $hasDifferent = true;
        foreach ($aRevision->original_values as $key => $value) {
            if ($oldModel[$key] !== $value) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertTrue($hasDifferent, "Attribute values of revisiopn and the old Model should match");

        return $model;
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

        $model = $this->setupModel(DefaultPrimaryKey::class);
        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        }

        // Create and updated a different Model
        $model2 = $this->setupModel(DefaultPrimaryKey::class);
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($model2->getFillable()) as $key) {
                $model2[$key] = $faker->name;
            }
            $model2->save();
        }

        $revisionCount = $model->allRevisions()->get()->count();

        if ($model->getKeyName() === "id" || $model->incrementing === true) {
            $this->assertEquals($updateCount + 1, $revisionCount, "Revision count should be " . ($updateCount + 1));
        } else {
            $this->assertEquals(2, $revisionCount, "Revision count should be 2");
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

        $model = $this->setupModel(DefaultPrimaryKey::class);
        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        }

        $latestRevisionId = $model->allRevisions()->latest('id')->first()->id;

        $singleRevision = $model->getRevision($latestRevisionId);

        $modelIdentifiers = $model->modelIdentifier();

        $this->assertEquals($modelIdentifiers, $singleRevision->model_identifier, "Identifiers do not match");
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

        $model = $this->setupModel(DefaultPrimaryKey::class);
        $oldModel = clone $model;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        }

        $aRevision = ($model->allRevisions()->orderBy('id', 'asc')->get()[1]);

        $model->rollback($aRevision->id, true);

        $hasDifferent = true;
        foreach ($model->getFillable() as $key) {
            if ($oldModel[$key] !== $model[$key]) {
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

        $model = $this->setupModel(DefaultPrimaryKey::class);

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        }

        $aRevision = $model->allRevisions()->orderBy('id', 'asc')->first();

        $model->rollback($aRevision->id, false);

        $revisionCount = $model->allRevisions()->where('id', '>=', $aRevision->id)->count();
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

        $model = $this->setupModel(DefaultPrimaryKey::class);

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        }

        config(['revision_tracking.remove_on_delete' => true]);

        $model->delete();

        $revisionCount = $model->allRevisions()->count();

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

        $model = $this->setupModel(DefaultPrimaryKey::class);

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        }

        config(['revision_tracking.remove_on_delete' => false]);

        $model->delete();

        $revisionCount = $model->allRevisions()->count();

        $this->assertEquals($updateCount + 2, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if an ErrorException will be thrown if a Model want to rollback to a revision which does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function testNoRevisionException()
    {
        try {
            $faker = \Faker\Factory::create();

            $model = $this->setupModel(DefaultPrimaryKey::class);

            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();

            $model->rollback(10);

        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }
}