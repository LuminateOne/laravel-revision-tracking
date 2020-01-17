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
     * It will check the number of revision created
     * It will check if the parent_revision of the Child revision equals to the Parent revision identifiers
     * It will check if the child_revisions of the Parent revision contains to the Child revision identifiers
     */
    public function testRelationUpdate()
    {
        $faker = \Faker\Factory::create();

        $modelGrandParent = $this->setupModel(GrandParent::class);

        (new ParentWithRevision())->createTable();
        (new Child())->createTable();

        $insertCount = 3;
        for($i = 0; $i < 3; $i ++){
            $modelCParent = new ParentWithRevision();
            foreach (($modelCParent->getFillable()) as $key) {
                $modelCParent[$key] = $faker->name;
            }
            $modelCParent->grand_parent_id = $modelGrandParent->id;
            $modelCParent->save();

            for($o = 0; $o < 3; $o ++){
                $child = new Child();
                foreach (($child->getFillable()) as $key) {
                    $child[$key] = $faker->name;
                }
                $child->parent_with_revision_id = $modelCParent->id;
                $child->save();
            }
        }

        $modelGrandParent = GrandParent::find(1)->with([
            'parentWithRevision' => function ($parent) {
                $parent->with('children');
            }
        ])->first();

        foreach (($modelGrandParent->getFillable()) as $key) {
            $modelGrandParent[$key] = $faker->name;
        }

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            foreach (($aParentWithRevision->getFillable()) as $key) {
                $aParentWithRevision[$key] = $faker->name;
            }

            foreach ($aParentWithRevision->children as $aChild) {
                foreach (($aChild->getFillable()) as $key) {
                    $aChild[$key] = $faker->name;
                }
            }
        }

        $modelGrandParent->push();

        $grandParentRevision = $modelGrandParent->allRevisions()->where('child_revisions', '!=', '')->first();
        $this->assertEquals($insertCount, count($grandParentRevision->child_revisions),
            "The child revision count of GrandParent should be " . $insertCount);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $aParentRevision = $aParentWithRevision->allRevisions()->latest('id')->first();

            $this->assertContains($aParentRevision->revisionIdentifier(), $grandParentRevision->child_revisions,
                "The child_revision of GrandParent revision should contain the revisions` identifiers of Parent model");

            $this->assertEquals($grandParentRevision->revisionIdentifier(), $aParentRevision->parent_revision,
                "The parent_revision of the revision`s identifiers of Parent model should equal to the GrandParent revision identifiers");

            $this->assertEquals($insertCount, count($aParentRevision->child_revisions),
                "The child_revision count of Parent should be " . $insertCount);

            foreach ($aParentWithRevision->children as $aChild) {
                $aChildRevision = $aChild->allRevisions()->latest('id')->first();

                $this->assertContains($aChildRevision->revisionIdentifier(), $aParentRevision->child_revisions,
                    "The child_revision of Parent revision should contain the revisions` identifiers of Child model");

                $this->assertEquals($aParentRevision->revisionIdentifier(), $aChildRevision->parent_revision,
                    "The parent_revision of the revision`s identifiers of Child model should equal to the Parent revision identifiers");

                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }
    }

    /**
     * Test update is a relation is loaded
     * It will create GrandParent, PraentWithRevision, ParentNoRevision, Child
     * It will check if the parent_revision of the Child revision equals to the Parent revision identifiers
     * It will check if the child_revisions of the Parent revision contains to the Child revision identifiers
     */
    public function testWithAModelDoesNotHaveRevisionTrackingRelationUpdate()
    {
        $faker = \Faker\Factory::create();

        $modelGrandParent = $this->setupModel(GrandParent::class);

        (new ParentWithRevision())->createTable();
        (new ParentNoRevision())->createTable();
        (new Child())->createTable();

        $insertCount = 3;
        for($i = 0; $i < 3; $i ++){
            $modelCParent = new ParentWithRevision();
            foreach (($modelCParent->getFillable()) as $key) {
                $modelCParent[$key] = $faker->name;
            }
            $modelCParent->grand_parent_id = $modelGrandParent->id;
            $modelCParent->save();

            for($o = 0; $o < 3; $o ++){
                $child = new Child();
                foreach (($child->getFillable()) as $key) {
                    $child[$key] = $faker->name;
                }
                $child->parent_with_revision_id = $modelCParent->id;
                $child->save();
            }

            $modelCParent2 = new ParentNoRevision();
            foreach (($modelCParent2->getFillable()) as $key) {
                $modelCParent2[$key] = $faker->name;
            }
            $modelCParent2->grand_parent_id = $modelGrandParent->id;
            $modelCParent2->save();

            for($o = 0; $o < 3; $o ++){
                $child = new Child();
                foreach (($child->getFillable()) as $key) {
                    $child[$key] = $faker->name;
                }
                $child->parent_no_revision_id = $modelCParent2->id;
                $child->save();
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

        foreach (($modelGrandParent->getFillable()) as $key) {
            $modelGrandParent[$key] = $faker->name;
        }

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            foreach (($aParentWithRevision->getFillable()) as $key) {
                $aParentWithRevision[$key] = $faker->name;
            }

            foreach ($aParentWithRevision->children as $aChild) {
                foreach (($aChild->getFillable()) as $key) {
                    $aChild[$key] = $faker->name;
                }
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            foreach (($aParentNoRevision->getFillable()) as $key) {
                $aParentNoRevision[$key] = $faker->name;
            }

            foreach ($aParentNoRevision->children as $aChild) {
                foreach (($aChild->getFillable()) as $key) {
                    $aChild[$key] = $faker->name;
                }
            }
        }

        $modelGrandParent->push();

        $grandParentRevision = $modelGrandParent->allRevisions()->where('child_revisions', '!=', '')->latest('id')->first();
        $this->assertEquals(($insertCount * $insertCount) + $insertCount, count($grandParentRevision->child_revisions),
            "The child revision count of GrandParent should be " . $insertCount);

        foreach ($modelGrandParent->parentWithRevision as $aParentWithRevision) {
            $aParentRevision = $aParentWithRevision->allRevisions()->latest('id')->first();

            $this->assertContains($aParentRevision->revisionIdentifier(), $grandParentRevision->child_revisions,
                "The child_revision of GrandParent revision should contain the revisions` identifiers of Parent model");

            $this->assertEquals($grandParentRevision->revisionIdentifier(), $aParentRevision->parent_revision,
                "The parent_revision of the revision`s identifiers of Parent model should equal to the GrandParent revision identifiers");

            $this->assertEquals($insertCount, count($aParentRevision->child_revisions),
                "The child_revision count of Parent should be " . $insertCount);

            foreach ($aParentWithRevision->children as $aChild) {
                $aChildRevision = $aChild->allRevisions()->latest('id')->first();

                $this->assertContains($aChildRevision->revisionIdentifier(), $aParentRevision->child_revisions,
                    "The child_revision of Parent revision should contain the revisions` identifiers of Child model");

                $this->assertEquals($aParentRevision->revisionIdentifier(), $aChildRevision->parent_revision,
                    "The parent_revision of the revision`s identifiers of Child model should equal to the Parent revision identifiers");

                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }

        foreach ($modelGrandParent->parentNoRevision as $aParentNoRevision) {
            foreach ($aParentNoRevision->children as $aChild) {
                $aChildRevision = $aChild->allRevisions()->latest('id')->first();

                $this->assertContains($aChildRevision->revisionIdentifier(), $grandParentRevision->child_revisions,
                    "The child_revision of GrandParent revision should contain the revisions` identifiers of Child model");

                $this->assertEquals($grandParentRevision->revisionIdentifier(), $aChildRevision->parent_revision,
                    "The parent_revision of the revision`s identifiers of Child model should equal to the GrandParent revision identifiers");

                $this->assertEmpty($aChildRevision->child_revisions,
                    "The child_revision count of Parent should be empty");
            }
        }
    }
}