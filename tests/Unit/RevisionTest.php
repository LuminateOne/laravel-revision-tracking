<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\TableOneUnique;
use LuminateOne\RevisionTracking\TestModels\customizedPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LuminateOne\RevisionTracking\RevisionTracking;
use Tests\TestCase;
use Faker\Generator as Faker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $testModelName = [
            'LuminateOne\RevisionTracking\Tests\Models\DefaultPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey',
        ][0];

        $this->testModel = new $testModelName();

        // Create a Model for testing
        // $faker = \Faker\Factory::create();
        // foreach (($this->testModel->getFillable()) as $key) {
        //     $this->testModel[$key] = $faker->name;
        // }
        // $this->testModel->save();

        if ($this->testModel->revisionMode() === 'single') {
            Schema::create(config('revision_tracking.table_prefix', 'revisions_') . $testModel->getTable(),
                function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->text('revision_identifier');
                    $table->text('original_values');
                    $table->timestamps();
                });
        }
    }

    /**
     * Test if the updated event can be catched be Revisionable.
     *
     * @return $insertedRecord  Clone the created the model.
     */
    public function testUpdate()
    {
        $faker = \Faker\Factory::create();

        // Create a Model for testing
        $modelName = 'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey';
        $record = new $modelName();
        foreach (($record->getFillable()) as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();

        $oldRecord = clone $record;

        foreach (($record->getFillable()) as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();

        $originalValuesChanged = RevisionTracking::eloquentDiff($record);

        $aRevision = $record->allRevisions()->latest('id')->first();

        $modelIdentifiers = [$record->getKeyName() => $record->getKey()];

        // Check if the revision identifier are equal
        $this->assertEquals($modelIdentifiers, $aRevision->revision_identifier,
            'The identifiers of revision and the primary key of the Model should match');

        // Check if the values stored in the revision table equals to the old record
        $hasDifferent = true;
        foreach ($aRevision->original_values as $value) {
            if ($oldRecord[$value['column']] !== $value['value']) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertTrue($hasDifferent, "Attribute values of revisiopn and the old Model should match");

        return $record;
    }

    /**
     * Test get all revisions
     * This will create and updated two different Model,
     * So it can test if the "allRevision" method only return
     * the revisions that belongs to the current Model
     *
     * @throws ErrorException
     */
    public function testGetAllRevision()
    {
        $faker = \Faker\Factory::create();

        // Create and update Model for testing
        $modelName = 'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey';
        $record = new $modelName();
        foreach (($record->getFillable()) as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();
        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        // Create and updated a different Model
        $modelName2 = 'LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey';
        $record2 = new $modelName2();
        foreach (($record2->getFillable()) as $key) {
            $record2[$key] = $faker->name;
        }
        $record2->save();
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record2->getFillable()) as $key) {
                $record2[$key] = $faker->name;
            }
            $record->save();
        }

        // Restore the revision
        $revisionCount = $record->allRevisions()->get()->count();

        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $this->assertEquals($updateCount, $revisionCount, "Revision count should be " . $updateCount);
        } else {
            $this->assertEquals(1, $revisionCount, "Revision count should be 1");
        }
    }

    /**
     * Test get a single revisions by revision ID
     * This will create and updated Model,
     * Then get the latest revision, and check if the
     *
     * @throws \ErrorException
     */
    public function testGetRevision()
    {
        $faker = \Faker\Factory::create();

        // Create and update Model for testing
        $modelName = 'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey';
        $record = new $modelName();
        foreach ($record->getFillable() as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();
        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        $singleRevision = $record->getRevision($updateCount);

        $modelIdentifiers = [$record->getKeyName() => $record->getKey()];

        $this->assertEquals($modelIdentifiers, $singleRevision->revision_identifier, "Identifiers do not match");
    }

    /**
     * Test rollback, it will insert a new recored, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     *
     * @throws \ErrorException
     */
    public function testRollback()
    {
        $faker = \Faker\Factory::create();

        // Create and update Model for testing
        $modelName = 'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey';
        $record = new $modelName();
        foreach ($record->getFillable() as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();

        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        $saveAsRevision = true;

        $latestRevision = $record->getRevision(1);

        $record->rollback(1, $saveAsRevision);

        $restoredRecord = (new $modelName())->find($latestRevision->revision_identifier)->first();

        $hasDifferent = true;
        foreach ($record->getFillable() as $key) {
            if ($oldRecord[$key] !== $restoredRecord[$key]) {
                $hasDifferent = false;
                break;
            }
        }
        $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');


        if (!$saveAsRevision) {
            $revisionCount = $record->allRevisions()->where([['id', '>=', $revisionId]])->count();
            $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
        }
    }


    /**
     * Test delete
     * Test will the revision be deleted after delete a Model
     *
     * @throws \ErrorException
     */
    public function testDelete()
    {
        // Create and update Model for testing
        $modelName = 'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey';
        $record = new $modelName();
        foreach ($record->getFillable() as $key) {
            $record[$key] = $faker->name;
        }
        $record->save();
        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            foreach (($record->getFillable()) as $key) {
                $record[$key] = $faker->name;
            }
            $record->save();
        }

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $record->delete();

        $revisionCount = $record->allRevisions()->count();

        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }
}