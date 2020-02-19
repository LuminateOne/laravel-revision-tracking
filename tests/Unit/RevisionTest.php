<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\NoPrimaryKey;
use LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey;
use LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKeyWithSoftDelete;

class RevisionTest extends TestCase
{
    /**
     * Test Exception when the revision table not exist
     *
     * @throws \Exception
     */
    public function testNoRevisionTableException(){
        config(['revision_tracking.mode' => 'all']);
        $this->noRevisionTableException();

        config(['revision_tracking.mode' => 'single']);
        $this->noRevisionTableException();

        $this->setupRevisionTable();
    }

    /**
     * Test revision mode all
     *
     * @throws \Exception
     */
    public function testModeAll()
    {
        config(['revision_tracking.mode' => 'all']);

        $this->noRevisionTableException();
        $this->update();
        $this->getAllRevision();
        $this->getRevision();
        $this->rollback();
        $this->removeOnDelete(false);
        $this->removeOnDelete(true);
        $this->noRevisionException();
        $this->noPrimaryKeyException();
        $this->softDelete(true);
        $this->softDelete(false);
    }

    /**
     * Test revision mode single
     *
     * @throws \Exception
     */
    public function testModeSingle()
    {
        config(['revision_tracking.mode' => 'single']);

        $this->update();
        $this->getAllRevision();
        $this->getRevision();
        $this->rollback();
        $this->removeOnDelete(false);
        $this->removeOnDelete(true);
        $this->noRevisionException();
        $this->noPrimaryKeyException();
        $this->softDelete(true);
        $this->softDelete(false);
    }

    /**
     * Test if an ErrorException will be thrown if the revision table does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    private function noRevisionTableException()
    {
        try {
            $model = $this->setupModel(DefaultPrimaryKey::class);
            $this->updateModel($model);
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
    private function update()
    {
        $model = $this->setupModel(DefaultPrimaryKey::class);
        $oldModel = clone $model;

        $model = $this->updateModel($model);

        $aRevision = $model->allRevisions()->latest('id')->first();

        $modelIdentifiers = $model->modelIdentifier();

        $this->assertEquals($modelIdentifiers, $aRevision->model_identifier,
            'The identifiers of revision and the primary key of the Model should match');

        $this->assertEquals(1, $model->allRevisions()->count(), 'The count of reviions should be 1');

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
    private function getAllRevision()
    {
        $updateCount = 3;

        $model = $this->setupModel(DefaultPrimaryKey::class);
        $model = $this->updateModel($model, $updateCount);

        $model2 = $this->setupModel(DefaultPrimaryKey::class);
        $this->updateModel($model2, $updateCount);

        $revisionCount = $model->allRevisions()->get()->count();

        if ($model->getKeyName() === "id" || $model->incrementing === true) {
            $this->assertEquals($updateCount, $revisionCount, "Revision count should be " . $updateCount);
        } else {
            $this->assertEquals(0, $revisionCount, "Revision count should be 0");
        }
    }

    /**
     * Test get a single revision by revision ID
     * This will create and update a model,
     * Then get the latest revision, and check if the identifiers are the same
     */
    private function getRevision()
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
    private function rollback()
    {
        $model = $this->setupModel(DefaultPrimaryKey::class);
        $oldModel = clone $model;
        $model = $this->updateModel($model, 3);
        $aRevision = ($model->allRevisions()->orderBy('id', 'asc')->get())[0];

        $model->rollback($aRevision->id, true);
        $hasDifferent = $this->compareTwoModel($oldModel, $model);
        $this->assertEquals(false, $hasDifferent, 'Fillable attribute values do not match');

        $aRevision = $model->allRevisions()->orderBy('id', 'asc')->first();
        $model->rollback($aRevision->id, false);
        $revisionCount = $model->allRevisions()->count();
        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if the revision will be deleted after deleting a Model
     *
     * @param boolean $deleteRevision
     *
     * @throws \Exception  If the Model does not have a primary key
     *                          If the Model does not have any revision
     */
    private function removeOnDelete($deleteRevision)
    {
        $model = $this->setupModel(DefaultPrimaryKey::class);

        $updateCount = 3;
        $model = $this->updateModel($model, $updateCount);

        config(['revision_tracking.remove_on_delete' => $deleteRevision]);

        $model->delete();

        $revisionCount = $model->allRevisions()->count();

        $expected = $deleteRevision ? 0 : ($updateCount + 1);
        $this->assertEquals($expected, $revisionCount, 'The revisions are not deleted');
    }

    /**
     * Test if an ErrorException will be thrown if a Model want to rollback to a revision which does not exist
     *
     * @throws \Exception If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    private function noRevisionException()
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
    private function noPrimaryKeyException()
    {
        try {
            $model = $this->setupModel(NoPrimaryKey::class);
            $this->updateModel($model);
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }

    /**
     * Test if soft deleted model rollback
     *
     * @param boolean $deleteRevision
     * @throws \ErrorException If the Model does not have a primary key
     *                    If the Model does not have any revision
     */
    private function softDelete($deleteRevision = true){
        $this->setupRevisionTable();
        $model = $this->setupModel(DefaultPrimaryKeyWithSoftDelete::class);

        $model = $this->updateModel($model);

        config(['revision_tracking.remove_on_delete' => $deleteRevision]);
        $model = DefaultPrimaryKeyWithSoftDelete::withTrashed()->where('id', $model->id)->first();
        $model->forceDelete();
        $expected = $deleteRevision ? 0 : 2;
        $this->assertEquals($expected, $model->allRevisions()->count(), 'The number of revisions should be ' . $expected);

        $model = $this->setupModel(DefaultPrimaryKeyWithSoftDelete::class);
        $model = DefaultPrimaryKeyWithSoftDelete::find($model->id);
        $model->delete();
        $revision = $model->allRevisions()->latest('id')->first();
        $this->assertEquals(1, $model->allRevisions()->count(), 'The number of revisions should be 1');
        $this->assertNull($revision->original_values['deleted_at'], 'The deleted_at should be null');
        $this->assertEquals(true, $model->trashed(), 'The model should be trashed');

        $model = DefaultPrimaryKeyWithSoftDelete::withTrashed()->where('id', $model->id)->first();
        $model->rollback($revision->id, true);
        $this->assertEquals(false, $model->trashed(), 'The model should not be trashed');
        $this->assertEquals(2, $model->allRevisions()->count(), 'The number of revisions should be 2');

        $model = DefaultPrimaryKeyWithSoftDelete::withTrashed()->where('id', $model->id)->first();
        $model->delete();
        $model = DefaultPrimaryKeyWithSoftDelete::withTrashed()->where('id', $model->id)->first();
        $model->restore();
        $revision = $model->allRevisions()->latest('id')->first();
        $this->assertNotNull($revision->original_values['deleted_at'], 'The deleted_at should not be null');

        $model->rollback($revision->id);
        $this->assertEquals(true, $model->trashed(), 'The model should be trashed');
    }
}