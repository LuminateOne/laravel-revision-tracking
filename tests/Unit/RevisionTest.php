<?php

namespace LuminateOne\RevisionTracking\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\NoPrimaryKey;
use LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey;

class RevisionTest extends TestCase
{
    /**
     * Test revision mode all
     *
     * @throws \Exception
     */
    public function testModeAll()
    {
        config(['revision_tracking.mode' => 'all']);

        $this->noRevisionTableException();
        $this->setupRevisionTable();
        $this->update();
        $this->getAllRevision();
        $this->getRevision();
        $this->rollback();
        $this->removeOnDelete(false);
        $this->removeOnDelete(true);
        $this->noRevisionException();
        $this->noPrimaryKeyException();
    }


    /**
     * Test revision mode single
     *
     * @throws \Exception
     */
    public function testModeSingle()
    {
        config(['revision_tracking.mode' => 'single']);

        $this->noRevisionTableException();
        $this->update();
        $this->getAllRevision();
        $this->getRevision();
        $this->rollback();
        $this->removeOnDelete(false);
        $this->removeOnDelete(true);
        $this->noRevisionException();
        $this->noPrimaryKeyException();
    }

    /**
     * Test if an ErrorException will be thrown if the revision table does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function noRevisionTableException()
    {
        try {
            $this->setupModel(DefaultPrimaryKey::class);
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }


    /**
     * Test if the updated event can be caught by Revisionable.
     * It will check if a new revision is created after update the Model
     * It will check if the original_values stored in the revision table are equals to the old Model
     */
    public function update()
    {
        $model = $this->setupModel(DefaultPrimaryKey::class);
        $oldModel = clone $model;

        $model = $this->updateModel($model);

        $aRevision = $model->allRevisions()->latest('id')->first();

        $modelIdentifiers = $model->modelIdentifier();

        $this->assertEquals($modelIdentifiers, $aRevision->model_identifier,
            'The identifiers of revision and the primary key of the Model should match');

        $this->assertEquals(2, $model->allRevisions()->count(), 'The count of reviions should be 2');

        $hasDifferent = true;
        foreach ($aRevision->original_values as $key => $value) {
            if ($oldModel[$key] !== $value) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertTrue($hasDifferent, "Attribute values of revision and the old Model should match");

        return $model;
    }

    /**
     * Test get all revisions
     * This will create and updated two different Models,
     * So it can test if the "allRevision" method only returns
     * the revisions that belong to the current Model
     */
    public function getAllRevision()
    {
        $updateCount = 3;

        $model = $this->setupModel(DefaultPrimaryKey::class);
        $model = $this->updateModel($model, $updateCount);

        $model2 = $this->setupModel(DefaultPrimaryKey::class);
        $this->updateModel($model2, $updateCount);

        $revisionCount = $model->allRevisions()->get()->count();

        if ($model->getKeyName() === "id" || $model->incrementing === true) {
            $this->assertEquals($updateCount + 1, $revisionCount, "Revision count should be " . $updateCount);
        } else {
            $this->assertEquals(2, $revisionCount, "Revision count should be 2");
        }
    }

    /**
     * Test get a single revision by revision ID
     * This will create and update a model,
     * Then get the latest revision, and check if the identifiers are the same
     */
    public function getRevision()
    {
        $model = $this->setupModel(DefaultPrimaryKey::class);
        $model = $this->updateModel($model, 3);

        $latestRevisionId = $model->allRevisions()->latest('id')->first()->id;

        $singleRevision = $model->getRevision($latestRevisionId);

        $modelIdentifiers = $model->modelIdentifier();

        $this->assertEquals($modelIdentifiers, $singleRevision->model_identifier, "Identifiers do not match");
    }

    /**
     * Test rollback, it will insert a new record, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     */
    public function rollback()
    {
        $model = $this->setupModel(DefaultPrimaryKey::class);
        $oldModel = clone $model;
        $model = $this->updateModel($model, 3);

        $aRevision = ($model->allRevisions()->orderBy('id', 'asc')->get())[1];

        $model->rollback($aRevision->id, true);

        $hasDifferent = true;
        foreach ($model->getFillable() as $key) {
            if ($oldModel[$key] !== $model[$key]) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');

        $aRevision = $model->allRevisions()->orderBy('id', 'asc')->first();
        $model->rollback($aRevision->id, false);
        $revisionCount = $model->allRevisions()->count();
        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if the revision will be deleted after deleting a Model
     *
     * @param boolean $deleteRevision
     */
    public function removeOnDelete($deleteRevision)
    {
        $model = $this->setupModel(DefaultPrimaryKey::class);

        $updateCount = 3;
        $model = $this->updateModel($model, $updateCount);

        config(['revision_tracking.remove_on_delete' => $deleteRevision]);

        $model->delete();

        $revisionCount = $model->allRevisions()->count();

        $expected = $deleteRevision ? 0 : ($updateCount + 2);
        $this->assertEquals($expected, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if an ErrorException will be thrown if a Model want to rollback to a revision which does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    public function noRevisionException()
    {
        try {
            $model = $this->setupModel(DefaultPrimaryKey::class);
            $model = $this->updateModel($model);

            $model->rollback(10);
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
    public function noPrimaryKeyException()
    {
        try {
            $model = $this->setupModel(NoPrimaryKey::class);
            $this->updateModel($model);
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }
}