<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\Child;
use LuminateOne\RevisionTracking\Tests\Models\CParent;
use LuminateOne\RevisionTracking\Tests\Models\GrandParent;

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
     * It will check if the root_revision of the Child revision equals to the Root revision identifiers
     * It will check if the child_revision of the Root revision equals to the Child revision identifiers
     */
    public function testRelationUpdate()
    {
        $faker = \Faker\Factory::create();

        $modelGrandParent = $this->setupModel(GrandParent::class);

        (new CParent())->createTable();
        (new Child())->createTable();

        $insertCount = 3;
        for($i = 0; $i < 3; $i ++){
            $modelCParent = new CParent();
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
                $child->c_parent_id = $modelCParent->id;
                $child->save();
            }
        }

        $modelGrandParent = GrandParent::find(1)->with(['cParents' => function ($cParent) {
            $cParent->with('children');
        }])->first();

        foreach (($modelGrandParent->getFillable()) as $key) {
            $modelGrandParent[$key] = $faker->name;
        }

        foreach ($modelGrandParent->cParents as $aCParent) {
            foreach (($aCParent->getFillable()) as $key) {
                $aCParent[$key] = $faker->name;
            }

            foreach ($aCParent->children as $aChild) {
                foreach (($aChild->getFillable()) as $key) {
                    $aChild[$key] = $faker->name;
                }
            }
        }

        $modelGrandParent->push();

        $relationRevision = $modelGrandParent->allRevisions()->where('child_revisions', '!=', '')->first();

        $cParentRevision = $modelGrandParent->cParents[0]->allRevisions()->latest('id')->first();
        $this->assertEquals($relationRevision->child_revisions[0], $cParentRevision->revisionIdentifier(),
            "The child_revision of Root revision identifiers should equal to the Child revision identifiers");

        $this->assertEquals($relationRevision->revisionIdentifier(), $cParentRevision->root_revision,
            "The root_revision of Child revision identifiers should equal to the Root revision identifiers");

        $expected = (($insertCount + 1) * $insertCount);
        $this->assertEquals($expected, count($relationRevision->child_revisions), "Relation revision count should be " . $expected);
    }
}