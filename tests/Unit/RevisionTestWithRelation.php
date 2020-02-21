<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\Child;
use LuminateOne\RevisionTracking\Tests\Models\GrandParent;
use LuminateOne\RevisionTracking\Tests\Models\ParentWithRevision;
use LuminateOne\RevisionTracking\Tests\Models\ChildWithSoftDeletes;
use LuminateOne\RevisionTracking\Tests\Models\ParentWithSoftDeletes;
use LuminateOne\RevisionTracking\Tests\Models\ParentWithoutRevision;

class RevisionTestWithRelation extends TestCase
{
    /**
     * Setup the test
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupRevisionTable();
    }

    /**
     * Test relational revision mode all
     *
     * @throws \Exception
     */
    public function testRelationalRevisionModeAll(){
        config(['revision_tracking.mode' => 'all']);

        $this->noRelationLoadedException();
        $this->updateModelHasRelationLoaded(false);
        $this->updateModelHasRelationLoaded(true);
        $this->relationalWithSoftDeletes(false);
        $this->relationalWithSoftDeletes(true);
    }

    /**
     * Test relational revision mode single
     *
     * @throws \Exception
     */
    public function testRelationalRevisionModeSingle(){
        config(['revision_tracking.mode' => 'single']);

        $this->noRelationLoadedException();
        $this->updateModelHasRelationLoaded(false);
        $this->updateModelHasRelationLoaded(true);
        $this->relationalWithSoftDeletes(false);
        $this->relationalWithSoftDeletes(true);
    }

    private function noRelationLoadedException(){
        try {
            $modelGrandParent = $this->setupModel(GrandParent::class);
            $modelGrandParent->setAsRelationalRevision();
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
            return;
        }
    }

    /**
     * Test relational rollback after create revision and update models,
     * it will create GrandParent, ParentWithRevision, ParentWithoutRevision, Child
     *
     * After rollback, it will check the fillbale value between restored model and original model
     *
     * @param boolean $saveAsRevision
     */
    private function updateModelHasRelationLoaded($saveAsRevision)
    {
        // Create ParentWithRevision, ParentWithoutRevision, and Child model
        $insertCount = 3;
        $modelGrandParent = $this->setupModel(GrandParent::class);
        $modelGrandParentCopy = clone $modelGrandParent;

        for ($i = 0; $i < $insertCount; $i++) {
            $modelParent = $this->setupModel(ParentWithRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(Child::class, ['parent_with_revision_id' => $modelParent->id]);
            }
            $modelCParent2 = $this->setupModel(ParentWithoutRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(Child::class, ['parent_without_revision_id' => $modelCParent2->id]);
            }
        }

        // Load GrandParent with its relations
        $modelGrandParent = GrandParent::where('id', $modelGrandParent->id)->with([
            'parentsWithRevisions' => function ($parent) {
                $parent->with('children');
            },
            'parentsWithoutRevisions' => function ($parent) {
                $parent->with('children');
            }
        ])->first();

        // Set as relational revision
        $modelGrandParent->setAsRelationalRevision();
        $parentsWithRevisionArray = [];
        $childrenArray = [];

        foreach ($modelGrandParent->parentsWithRevisions as $aParentWithRevision) {
            array_push($parentsWithRevisionArray, clone $aParentWithRevision);
            $this->updateModel($aParentWithRevision);
            foreach ($aParentWithRevision->children as $aChild) {
                array_push($childrenArray, clone $aChild);
                $this->updateModel($aChild);
            }
        }

        foreach ($modelGrandParent->parentsWithoutRevisions as $aParentNoRevision) {
            $this->updateModel($aParentNoRevision);
            foreach ($aParentNoRevision->children as $aChild) {
                array_push($childrenArray, clone $aChild);
                $this->updateModel($aChild);
            }
        }
        $this->updateModel($modelGrandParent);

        // Set as relational revision, and create a new relational revision
        $modelGrandParent->setAsRelationalRevision();
        $this->updateModel($modelGrandParent);

        foreach ($modelGrandParent->parentsWithRevisions as $aParentWithRevision) {
            $this->updateModel($aParentWithRevision);
            foreach ($aParentWithRevision->children as $aChild) {
                $this->updateModel($aChild);
            }
        }

        foreach ($modelGrandParent->parentsWithoutRevisions as $aParentNoRevision) {
            $this->updateModel($aParentNoRevision);
            foreach ($aParentNoRevision->children as $aChild) {
                $this->updateModel($aChild);
            }
        }

        // Get the latest revision id and rollback with relation
        $grandParentRevision = $modelGrandParentCopy->allRelationalRevisions()->orderBy('id', 'asc')->first();
        $aRelationRevision = $modelGrandParentCopy->getRelationalRevision($grandParentRevision->id);
        $this->assertEquals($grandParentRevision, $aRelationRevision, "The two revisions should be equal");

        $changedGrandParent = GrandParent::find($modelGrandParentCopy->id);
        $changedGrandParent->rollback($grandParentRevision->id, $saveAsRevision);

        $expected = ($insertCount * $insertCount) + $insertCount + ($insertCount * $insertCount);
        $this->assertEquals($expected, count($grandParentRevision->child_revisions),"The child revision count of GrandParent should be " . $expected);

        $expected = $saveAsRevision ? 3 : 0;
        $this->assertEquals($expected, $changedGrandParent->allRevisions()->count(),"The revision count of GrandParent should be " . $expected);

        $restoredGrandParent = GrandParent::find($modelGrandParentCopy->id);
        $hasDifferent = $this->compareTwoModel($modelGrandParentCopy, $restoredGrandParent);
        $this->assertEquals(false, $hasDifferent, 'Fillable attribute values do not match');

        foreach ($parentsWithRevisionArray as $aParentWithRevision) {
            $restoredParentWithRevision = ParentWithRevision::find($aParentWithRevision->id);
            $hasDifferent = $this->compareTwoModel($aParentWithRevision, $restoredParentWithRevision);
            $this->assertEquals(false, $hasDifferent, 'Fillable attribute values do not match');
        }

        foreach ($childrenArray as $aChild) {
            $restoredChild = Child::find($aChild->id);
            $hasDifferent = $this->compareTwoModel($aChild, $restoredChild);
            $this->assertEquals(false, $hasDifferent, 'Fillable attribute values do not match');
        }
    }

    /**
     * Test relational rollback with soft delete turned on
     *
     * After rollback, it will check the if the model is trashed
     *
     * @param boolean $saveAsRevision
     */
    private function relationalWithSoftDeletes($saveAsRevision = false)
    {
        // Create ParentWithRevision, ParentWithoutRevision, and Child model
        $insertCount = 3;
        $modelGrandParent = $this->setupModel(GrandParent::class);
        for ($i = 0; $i < $insertCount; $i++) {
            $modelParent = $this->setupModel(ParentWithSoftDeletes::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(ChildWithSoftDeletes::class, ['parent_with_soft_deletes_id' => $modelParent->id]);
            }
        }

        // Load GrandParent with its relations
        $modelGrandParent = GrandParent::where('id', $modelGrandParent->id)->with([
            'parentsWithSoftDeletes' => function ($parent) {
                $parent->with('childrenWithSoftDeletes');
            }
        ])->first();

        // Set the the relational revision manually
        $modelGrandParent->setAsRelationalRevision();
        foreach ($modelGrandParent->parentsWithSoftDeletes as $aParentsWithSoftDeletes) {
            $aParentsWithSoftDeletes->delete();
            foreach ($aParentsWithSoftDeletes->childrenWithSoftDeletes as $aChildWithSoftDeletes) {
                $aChildWithSoftDeletes->delete();
            }
        }
        $grandParentRevision = $modelGrandParent->allRelationalRevisions()->latest('id')->first();
        $expected = ($insertCount * $insertCount) + $insertCount;
        $this->assertEquals($expected, count($grandParentRevision->child_revisions),"The child revision count of GrandParent should be " . $expected);

        // Load GrandParent with its relations
        $modelGrandParent = GrandParent::find($modelGrandParent->id);
        $grandParentRevision = $modelGrandParent->allRelationalRevisions()->latest('id')->first();
        $modelGrandParent->rollback($grandParentRevision->id, $saveAsRevision);
        $this->assertEquals($expected, count($grandParentRevision->child_revisions),"The child revision count of GrandParent should be " . $expected);

        $modelGrandParent = GrandParent::where('id', $modelGrandParent->id)->with([
            'parentsWithSoftDeletes' => function ($parent) {
                $parent->with('childrenWithSoftDeletes');
            }
        ])->first();
        foreach ($modelGrandParent->parentsWithSoftDeletes as $aParentsWithSoftDeletes) {
            $this->assertEquals(false, $aParentsWithSoftDeletes->trashed(),"The ParentsWithSoftDeletes should not be trashed");

            foreach ($aParentsWithSoftDeletes->childrenWithSoftDeletes as $aChildWithSoftDeletes) {
                $this->assertEquals(false, $aChildWithSoftDeletes->trashed(),"The ChildWithSoftDeletes should not be trashed");
            }
        }
    }
}