<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\Child;
use LuminateOne\RevisionTracking\Tests\Models\GrandParent;
use LuminateOne\RevisionTracking\Tests\Models\ParentNoRevision;
use LuminateOne\RevisionTracking\Tests\Models\ParentWithRevision;

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
        $this->relationUpdate();
        $this->updateModelManually(false);
        $this->updateModelManually(true);
    }

    /**
     * Test relational revision mode single
     *
     * @throws \Exception
     */
    public function testRelationalRevisionModeSingle(){
        config(['revision_tracking.mode' => 'single']);
        $this->noRelationLoadedException();
        $this->relationUpdate();
        $this->updateModelManually(false);
        $this->updateModelManually(true);
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
     * Test relational rollback, it will create GrandParent, ParentWithRevision, ParentNoRevision, Child
     *
     * In the relationship of GrandParent           hasMany ParentWithRevision (Revision tracking turned on)
     *                        ParentWithRevision    hasMany Child
     *
     *      It will check if the parent revision of the Child revision equals the Parent revision
     *      It will check if the child revisions of the ParentWithRevision contains the Child
     *      It will check if the parent revision of the Child is Parent
     *
     * In the relationship of GrandParent       hasMany ParentNoRevision (Revision tracking turned off)
     *                        ParentNoRevision  hasMany Child
     *
     *      It will check if the parent revision of the Child is GrandParent
     *
     */
    private function relationUpdate()
    {
        // Create ParentWithRevision, ParentNoRevision, and Child model
        $insertCount = 3;
        $modelGrandParent = $this->setupModel(GrandParent::class);
        for ($i = 0; $i < $insertCount; $i++) {
            $modelParent = $this->setupModel(ParentWithRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(Child::class, ['parent_with_revision_id' => $modelParent->id]);
            }
            $modelCParent2 = $this->setupModel(ParentNoRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(Child::class, ['parent_no_revision_id' => $modelCParent2->id]);
            }
        }

        // Load relations
        $modelGrandParent->load([
            'parentWithRevision' => function ($parent) {
                $parent->with('children');
            },
            'parentNoRevision' => function ($parent) {
                $parent->with('children');
            }
        ]);

        // Fill the model with new values
        $this->fillModelWithNewValue($modelGrandParent);
        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $this->fillModelWithNewValue($aParentWithRevision);
            foreach ($aParentWithRevision->children as $aChild) {
                $this->fillModelWithNewValue($aChild);
            }
        }
        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            $this->fillModelWithNewValue($aParentNoRevision);
            foreach ($aParentNoRevision->children as $aChild) {
                $this->fillModelWithNewValue($aChild);
            }
        }

        // Call push() to update the models
        $modelGrandParent->push();

        $grandParentRevision = $modelGrandParent->allRelationalRevisions()->latest('id')->first();

        $expected = ($insertCount * $insertCount) + $insertCount + ($insertCount * $insertCount);
        $this->assertEquals($expected, count($grandParentRevision->child_revisions),"The child revision count of GrandParent should be " . $expected);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $this->assertEquals(GrandParent::class, get_class($aParentWithRevision->parentModel),
                "The parent model of Parent model should be " . GrandParent::class);

            $this->assertNull($aParentWithRevision->allRelationalRevisions()->latest('id')->first(), "The Parent should not have relational revision");

            $this->assertContains($aParentWithRevision->self_revision_identifier, $grandParentRevision->child_revisions,
                "The child_revision of GrandParent model should contains the revision of Parent model");

            foreach ($aParentWithRevision->children as $aChild) {
                $this->assertEquals(GrandParent::class, get_class($aParentWithRevision->parentModel),
                    "The parent model of Child model should be " . GrandParent::class);

                $this->assertNull($aChild->allRelationalRevisions()->latest('id')->first(), "The Child should not have relational revision");

                $this->assertContains($aChild->self_revision_identifier, $grandParentRevision->child_revisions,
                    "The child_revision of Parent model should contains the revision of Child model");
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            foreach ($aParentNoRevision->children as $aChild) {
                $this->assertEquals(GrandParent::class, get_class($aParentWithRevision->parentModel),
                    "The parent model of Child model should be " . GrandParent::class);

                $this->assertNull($aChild->allRelationalRevisions()->latest('id')->first(), "The Child should not have relational revision");

                $this->assertContains($aChild->self_revision_identifier, $grandParentRevision->child_revisions,
                    "The child_revision of GrandParent revision should contain the revisions` identifiers of Child model");

            }
        }
    }

    /**
     * Test relational rollback after create revision and update models manually,
     * it will create GrandParent, ParentWithRevision, ParentNoRevision, Child
     *
     * After rollback, it will check the fillbale value between restored model and original model
     *
     * @param boolean $saveAsRevision
     */
    private function updateModelManually($saveAsRevision)
    {
        // Create ParentWithRevision, ParentNoRevision, and Child model
        $insertCount = 3;
        $modelGrandParent = $this->setupModel(GrandParent::class);
        for ($i = 0; $i < $insertCount; $i++) {
            $modelParent = $this->setupModel(ParentWithRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(Child::class, ['parent_with_revision_id' => $modelParent->id]);
            }
            $modelCParent2 = $this->setupModel(ParentNoRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(Child::class, ['parent_no_revision_id' => $modelCParent2->id]);
            }
        }

        // Load GrandParent with its relations
        $modelGrandParent = GrandParent::where('id', $modelGrandParent->id)->with([
            'parentWithRevision' => function ($parent) {
                $parent->with('children');
            },
            'parentNoRevision' => function ($parent) {
                $parent->with('children');
            }
        ])->first();

        // Set the the relational revision manually
        $modelGrandParent->setAsRelationalRevision();

        // Clone the original models and assign the model with new values
        $modelGrandParentCopy = clone $modelGrandParent;
        $parentsWithRevisionArray = [];
        $childrenArray = [];

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            array_push($parentsWithRevisionArray, clone $aParentWithRevision);
            $this->updateModel($aParentWithRevision);
            foreach ($aParentWithRevision->children as $aChild) {
                array_push($childrenArray, clone $aChild);
                $this->updateModel($aChild);
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            $this->updateModel($aParentNoRevision);
            foreach ($aParentNoRevision->children as $aChild) {
                array_push($childrenArray, clone $aChild);
                $this->updateModel($aChild);
            }
        }
        $this->updateModel($modelGrandParent);

        // Set the the relational revision manually
        $modelGrandParent->setAsRelationalRevision();
        $this->updateModel($modelGrandParent);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $this->updateModel($aParentWithRevision);
            foreach ($aParentWithRevision->children as $aChild) {
                $this->updateModel($aChild);
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            $this->updateModel($aParentNoRevision);
            foreach ($aParentNoRevision->children as $aChild) {
                $this->updateModel($aChild);
            }
        }

        // Get the latest revision id and rollback with relation
        $grandParentRevision = $modelGrandParentCopy->allRelationalRevisions()->orderBy('id', 'desc')->skip(1)->first();
        $aRelationRevision = $modelGrandParentCopy->getRelationalRevision($grandParentRevision->id);
        $this->assertEquals($grandParentRevision, $aRelationRevision, "The two revisions should be equal");

        $changedGrandParent = GrandParent::find($modelGrandParentCopy->id);
        $changedGrandParent->rollback($grandParentRevision->id, $saveAsRevision);

        $expected = ($insertCount * $insertCount) + $insertCount + ($insertCount * $insertCount);
        $this->assertEquals($expected, count($grandParentRevision->child_revisions),"The child revision count of GrandParent should be " . $expected);

        $expected = $saveAsRevision ? 4 : 1;
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
}