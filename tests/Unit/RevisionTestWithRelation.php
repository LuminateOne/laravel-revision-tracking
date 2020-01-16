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
     * Test if the updated event can be caught by Revisionable.
     * It will check if a new revision is created after update the Model
     * It will check if the original_values stored in the revision table are equals to the old Model
     *
     * @throws \ErrorException If the Model does not have a primary key
     */
    public function testRelation()
    {
        $faker = \Faker\Factory::create();

        $modelGrandParent = $this->setupModel(GrandParent::class);

        (new CParent())->createTable();
        (new Child())->createTable();
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
            // foreach (($aCParent->getFillable()) as $key) {
            //     $aCParent[$key] = $faker->name;
            // }

            foreach ($aCParent->children as $aChild) {
                foreach (($aChild->getFillable()) as $key) {
                    $aChild[$key] = $faker->name;
                }
            }
        }

        $modelGrandParent->push();
    }
}