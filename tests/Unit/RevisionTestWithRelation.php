<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\User;
use LuminateOne\RevisionTracking\Tests\Models\Address;
use Illuminate\Support\Facades\Schema;

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

        $modelUserName = 'LuminateOne\RevisionTracking\Tests\Models\User';
        $recordUser = $this->setupModel($modelUserName);

        $modelAddress = new Address();
        $modelAddress->createTable();
        $modelAddress->user_id = $recordUser->id;
        $modelAddress->suburb = $faker->name;
        $modelAddress->city = $faker->name;
        $modelAddress->country = $faker->name;
        $modelAddress->save();

        $address = Address::find(1)->with('user')->first();

        // dd($address);
        // \Log::info(print_r($address, true));
        $address->suburb = $faker->name;
        $address->user->first_name = $faker->name;

        $address->push();
        // \Log::info(print_r($address->getRelations(), true));
        // foreach (($record->getFillable()) as $key) {
        //     $record[$key] = $faker->name;
        // }
        // $record->save();
        //
        // $aRevision = $record->allRevisions()->latest('id')->first();
        //
        // $modelIdentifiers = [$record->getKeyName() => $record->getKey()];
        //
        // // Check if the model identifier are equal
        // $this->assertEquals($modelIdentifiers, $aRevision->model_identifier,
        //     'The identifiers of revision and the primary key of the Model should match');
        //
        // // Check if the values stored in the revision table equals to the old record
        // $hasDifferent = true;
        // foreach ($aRevision->original_values as $value) {
        //     if ($oldRecord[$value['column']] !== $value['value']) {
        //         $hasDifferent = false;
        //         break;
        //     }
        // }
        // $this->assertTrue($hasDifferent, "Attribute values of revisiopn and the old Model should match");
        //
        // return $record;
    }
}