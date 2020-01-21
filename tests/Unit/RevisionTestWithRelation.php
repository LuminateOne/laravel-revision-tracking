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
        $this->relationUpdate();
        $this->relationRollback();
        $this->noRevisionException();
    }

    /**
     * Test relational revision mode single
     *
     * @throws \Exception
     */
    public function testRelationalRevisionModeSingle(){
        config(['revision_tracking.mode' => 'single']);
        $this->relationUpdate();
        $this->relationRollback();
        $this->noRevisionException();
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

        $modelGrandParent->push();

        $grandParentRevision = $modelGrandParent->allRelationalRevisions()->latest('id')->first();

        $this->assertEquals(($insertCount * $insertCount) + $insertCount, count($grandParentRevision->child_revisions),
            "The child revision count of GrandParent should be " . $insertCount);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $aParentRevision = $aParentWithRevision->allRelationalRevisions()->latest('id')->first();

            $this->assertContains($aParentWithRevision->self_revision_identifier,
                $grandParentRevision->child_revisions,
                "The child_revision of GrandParent model should contains the revision of Parent model");
            $this->assertEquals($modelGrandParent->self_revision_identifier,
                $aParentRevision->parent_revision,
                "The parent_revision of Parent model should equal to the GrandParent revision identifiers");
            $this->assertEquals($insertCount, count($aParentRevision->child_revisions),
                "The child_revision count of Parent should be " . $insertCount);

            foreach ($aParentWithRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->self_revision_identifier, $aParentRevision->child_revisions,
                    "The child_revision of Parent model should contains the revision of Child model");
                $this->assertEquals($aParentWithRevision->self_revision_identifier,
                    $aChildRevision->parent_revision,
                    "The parent_revision of Child model should equal to the Parent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            foreach ($aParentNoRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->self_revision_identifier,
                    $grandParentRevision->child_revisions,
                    "The child_revision of GrandParent revision should contain the revisions` identifiers of Child model");
                $this->assertEquals($modelGrandParent->self_revision_identifier,
                    $aChildRevision->parent_revision,
                    "The parent_revision of the revision`s identifiers of Child model should equal to the GrandParent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }
    }

    /**
     * Test relational rollback, it will create GrandParent, ParentWithRevision, ParentNoRevision, Child
     * This will test setAsRelationalRevision function, the GrandParent will not be updated, so we need to set the relation manually
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
     * It will use a Child to perform the rollback action
     * After rollback, it will check the fillbale value between restored model and original model
     */
    private function relationRollback()
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

        $modelGrandParent = GrandParent::where('id', $modelGrandParent->id)->with([
            'parentWithRevision' => function ($parent) {
                $parent->with('children');
            },
            'parentNoRevision' => function ($parent) {
                $parent->with('children');
            }
        ])->first();

        $modelGrandParentCopy = clone $modelGrandParent;
        $parentsWithRevisionArray = [];
        $childrenArray = [];

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

        $modelGrandParent->setAsRelationalRevision();
        $modelGrandParent->push();

        $grandParentRevision = $modelGrandParent->allRelationalRevisions()->latest('id')->first();
        $this->assertEquals(null, $grandParentRevision->parent_revision, "GrandParent should not have parent revision");
        $this->assertEquals(($insertCount * $insertCount) + $insertCount, count($grandParentRevision->child_revisions),
            "The child revision count of GrandParent should be " . $insertCount);
        $this->assertEquals(null, $grandParentRevision->original_values, "GrandParent should not have original values");

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $aParentRevision = $aParentWithRevision->allRelationalRevisions()->latest('id')->first();

            $this->assertContains($aParentWithRevision->self_revision_identifier, $grandParentRevision->child_revisions,
                "The child_revision of GrandParent model should contain the revision of Parent model");
            $this->assertEquals($modelGrandParent->self_revision_identifier, $aParentRevision->parent_revision,
                "The parent_revision of Parent model should equal to the GrandParent revision identifiers");
            $this->assertEquals($insertCount, count($aParentRevision->child_revisions),
                "The child_revision count of Parent should be " . $insertCount);

            foreach ($aParentWithRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->self_revision_identifier, $aParentRevision->child_revisions,
                    "The child_revision of Parent model should contains the revision of Child model");
                $this->assertEquals($aParentWithRevision->self_revision_identifier, $aChildRevision->parent_revision,
                    "The parent_revision of Child model should equal to the Parent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            foreach ($aParentNoRevision->children as $aChild) {
                $aChildRevision = $aChild->allRelationalRevisions()->latest('id')->first();

                $this->assertContains($aChild->self_revision_identifier,
                    $grandParentRevision->child_revisions,
                    "The child_revision of GrandParent revision should contain the revisions of Child model");
                $this->assertEquals($modelGrandParent->self_revision_identifier,
                    $aChildRevision->parent_revision,
                    "The parent_revision of the revision`s identifiers of Child model should equal to the GrandParent revision identifiers");
                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }

        // Get the latest revision id and rollback with relation
        $aChildMode = $modelGrandParent->parentWithRevision[0]->children[0];
        $relationalRevisions = $aChildMode->allRelationalRevisions()->first();
        $aChildMode->rollbackWithRelation($relationalRevisions->id);

        $grandParentRevision = $modelGrandParentCopy->allRelationalRevisions()->latest('id')->first();
        $this->assertEquals(($insertCount * $insertCount) + $insertCount, count($grandParentRevision->child_revisions),"The child revision count of GrandParent should be " . $insertCount);
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

    private function noRevisionException(){
        $modelGrandParent = GrandParent::where('id', 1)->first();

        try{
            $modelGrandParent->rollbackWithRelation(1);
        } catch (\Throwable $exception){
            $this->assertInstanceOf(\ErrorException::class, $exception, 'An ErrorException should be thrown');
        }
    }
}