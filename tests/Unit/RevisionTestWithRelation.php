<?php

namespace LuminateOne\RevisionTracking\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\Child;
use LuminateOne\RevisionTracking\Tests\Models\GrandParent;
use LuminateOne\RevisionTracking\Tests\Models\ParentNoRevision;
use LuminateOne\RevisionTracking\Tests\Models\ParentWithRevision;

class RevisionTestWithRelation extends TestCase
{
    /**
     * Change the revision mode to all
     */
    public function setUp(): void
    {
        parent::setUp();

        config(['revision_tracking.mode' => 'all']);
    }

    /**
     * Test update is a relation is loaded
     * It will create GrandParent, PraentWithRevision, ParentNoRevision, Child
     * It will check if the parent_revision of the Child revision equals to the Parent revision identifiers
     * It will check if the child_revisions of the Parent revision contains to the Child revision identifiers
     */
    public function testRelationUpdate()
    {
        $modelGrandParent = $this->setupModel(GrandParent::class);

        (new ParentWithRevision())->createTable();
        (new ParentNoRevision())->createTable();
        (new Child())->createTable();

        $insertCount = 3;
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

        $modelGrandParent->load([
            'parentWithRevision' => function ($parent) {
                $parent->with('children');
            },
            'parentNoRevision' => function ($parent) {
                $parent->with('children');
            }
        ]);

        // $this->fillModelWithNewValue($modelGrandParent);

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

        $modelGrandParent->push();

        $grandParentRevision = $modelGrandParent->allRelationalRevisions()->latest('id')->first();
        $this->assertEquals(($insertCount * $insertCount) + $insertCount, count($grandParentRevision->child_revisions),
            "The child revision count of GrandParent should be " . $insertCount);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $aParentRevision = $aParentWithRevision->allRelationalRevisions()->latest('id')->first();

            $this->assertContains($aParentWithRevision->relationalRevisionIdentifier('self'),
                $grandParentRevision->child_revisions,
                "The child_revision of GrandParent model should contains the revision of Parent model");
            $this->assertEquals($modelGrandParent->relationalRevisionIdentifier('self'),
                $aParentRevision->parent_revision,
                "The parent_revision of Parent model should equal to the GrandParent revision identifiers");
            $this->assertEquals($insertCount, count($aParentRevision->child_revisions),
                "The child_revision count of Parent should be " . $insertCount);

            foreach ($aParentWithRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->relationalRevisionIdentifier('self'), $aParentRevision->child_revisions,
                    "The child_revision of Parent model should contains the revision of Child model");
                $this->assertEquals($aParentWithRevision->relationalRevisionIdentifier('self'),
                    $aChildRevision->parent_revision,
                    "The parent_revision of Child model should equal to the Parent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            foreach ($aParentNoRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->relationalRevisionIdentifier('self'),
                    $grandParentRevision->child_revisions,
                    "The child_revision of GrandParent revision should contain the revisions` identifiers of Child model");
                $this->assertEquals($modelGrandParent->relationalRevisionIdentifier('self'),
                    $aChildRevision->parent_revision,
                    "The parent_revision of the revision`s identifiers of Child model should equal to the GrandParent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }
    }

    /**
     * Test relational rollback
     * It will create GrandParent, PraentWithRevision, ParentNoRevision, Child
     * It will check if the parent_revision of the Child revision equals to the Parent revision identifiers
     * It will check if the child_revisions of the Parent revision contains to the Child revision identifiers
     */
    public function testRelationRollback()
    {
        $modelGrandParent = $this->setupModel(GrandParent::class);

        (new ParentWithRevision())->createTable();
        (new ParentNoRevision())->createTable();
        (new Child())->createTable();

        $insertCount = 3;
        for ($i = 0; $i < $insertCount; $i++) {
            $modelParent = $this->setupModel(ParentWithRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                $this->setupModel(Child::class, ['parent_with_revision_id' => $modelParent->id]);
            }
            $modelCParent2 = $this->setupModel(ParentNoRevision::class, ['grand_parent_id' => $modelGrandParent->id]);
            for ($o = 0; $o < $insertCount; $o++) {
                clone $this->setupModel(Child::class, ['parent_no_revision_id' => $modelCParent2->id]);
            }
        }

        $modelGrandParent->load([
            'parentWithRevision' => function ($parent) {
                $parent->with('children');
            },
            'parentNoRevision' => function ($parent) {
                $parent->with('children');
            }
        ]);

        $modelGrandParentCopy = clone $modelGrandParent;
        $parentsWithRevisionArray = [];
        $childrenArray = [];

        $this->fillModelWithNewValue($modelGrandParent);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            array_push($parentsWithRevisionArray, clone $aParentWithRevision);
            $this->fillModelWithNewValue($aParentWithRevision);
            foreach ($aParentWithRevision->children as $aChild) {
                array_push($childrenArray, clone $aChild);
                $this->fillModelWithNewValue($aChild);
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            $this->fillModelWithNewValue($aParentNoRevision);
            foreach ($aParentNoRevision->children as $aChild) {
                $this->fillModelWithNewValue($aChild);
            }
        }

        $modelGrandParent->push();
        $grandParentRevision = $modelGrandParent->allRelationalRevisions()->latest('id')->first();

        $this->assertEquals(($insertCount * $insertCount) + $insertCount, count($grandParentRevision->child_revisions),
            "The child revision count of GrandParent should be " . $insertCount);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $aParentRevision = $aParentWithRevision->allRelationalRevisions()->latest('id')->first();

            $this->assertContains($aParentWithRevision->relationalRevisionIdentifier('self'), $grandParentRevision->child_revisions,
                "The child_revision of GrandParent model should contain the revision of Parent model");
            $this->assertEquals($modelGrandParent->relationalRevisionIdentifier('self'), $aParentRevision->parent_revision,
                "The parent_revision of Parent model should equal to the GrandParent revision identifiers");
            $this->assertEquals($insertCount, count($aParentRevision->child_revisions),
                "The child_revision count of Parent should be " . $insertCount);

            foreach ($aParentWithRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->relationalRevisionIdentifier('self'), $aParentRevision->child_revisions,
                    "The child_revision of Parent model should contains the revision of Child model");
                $this->assertEquals($aParentWithRevision->relationalRevisionIdentifier('self'), $aChildRevision->parent_revision,
                    "The parent_revision of Child model should equal to the Parent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            foreach ($aParentNoRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->relationalRevisionIdentifier('self'),
                    $grandParentRevision->child_revisions,
                    "The child_revision of GrandParent revision should contain the revisions of Child model");
                $this->assertEquals($modelGrandParent->relationalRevisionIdentifier('self'),
                    $aChildRevision->parent_revision,
                    "The parent_revision of the revision`s identifiers of Child model should equal to the GrandParent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }

        // Get the latested revision id and rollback with relation
        $aChildMode = $modelGrandParent->parentWithRevision[0]->children[0];
        $relationalRevisions = $aChildMode->allRelationalRevisions()->first();
        $aChildMode->rollbackWithRelation($relationalRevisions->id);

        $grandParentRevision = $modelGrandParentCopy->allRelationalRevisions()->latest('id')->first();
        $this->assertEquals(($insertCount * $insertCount) + $insertCount, count($grandParentRevision->child_revisions),"The child revision count of GrandParent should be " . $insertCount);
        $restoredGrandParentWithRevision = GrandParent::find($modelGrandParentCopy->id);
        $hasDifferent = $this->compareTwoModel($modelGrandParentCopy, $restoredGrandParentWithRevision);
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