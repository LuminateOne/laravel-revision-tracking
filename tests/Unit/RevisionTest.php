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
    private $testModel = null;

    public function setUp(): void
    {
        parent::setUp();

        $testModelName = [
            'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\TableOneUnique',
        ][0];

        $this->testModel = new $testModelName();

        // Create a Model for testing
        $faker = \Faker\Factory::create();
        foreach (($this->testModel->getFillable()) as $key) {
            $this->testModel[$key] = $faker->name;
        }
        $this->testModel->save();

        if ($this->testModel->revisionMode() === 'single') {
            Schema::create(config('revision_tracking.table_prefix', 'revisions_') . $testModel->getTable(), function (Blueprint $table) {
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
    public function testUpdate(l)
    {
        $faker = \Faker\Factory::create();

        $record = $this->testModel;

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
        $this->assertTrue($hasDifferent, "Attribute values do not match");

        return $record;
    }

    /**
     * Test get all revisions
     * This will create and updated two different Model,
     * So it can test if the "allRevision" method only return
     * the revisions that belongs to the current Model
     *
     * @throws \ErrorException
     */
    public function testGetAllRevision()
    {
        $record = $this->testModel;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        // Create and updated a different Model
        $record2 = $this->createNewModel($this->modelProvider(1));
        for ($i = 0; $i < $updateCount; $i++) {
            $this->testUpdate($record2);
        }

        // Restore the revision
        $allReivisons = $record->allRevisions()->get();

        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $this->assertEquals($updateCount, count($allReivisons), "Revision count should be " . $updateCount);
        } else {
            $this->assertEquals(1, count($allReivisons), "Revision count should be " . $updateCount);
        }
    }

    /**
     * Test get revisions
     * Test differently when user set a customized primary key
     *
     * @throws \ErrorException
     */
    public function testGetRevision()
    {
        $record = $this->testModel;
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $revisionId = 1;
        //When user set the customized primary key
        if ($record->getKeyName() !== "id" || $record->incrementing !== true) {
            $revisionId = 3;
        }

        $singleRevision = $record->getRevision($revisionId);

        $this->assertEquals([$record->getKeyName() => $record->getKey()], $singleRevision->revision_identifier, "Identifiers do not match");

        // If the user did not set a customized primary key, then comppare the changed the fields
        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $hasDifferent = true;
            foreach ($singleRevision->original_values as $value) {
                if ($oldRecord[$value['column']] !== $value['value']) {
                    $hasDifferent = false;
                    break;
                }
            }
            $this->assertTrue($hasDifferent, "Attribute values do not match");
        }
    }

    /**
     * Test rollback, it will insert a new recored, and then update the record, then restore the revision.
     * Then check if the restored record is equal to the old record
     *
     * @throws \ErrorException
     */
    public function testRollback()
    {
        $record = $this->testModel;
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $saveAsRevision = true;

        $revisionId = 1;
        //Check if user set a customized primary key
        if ($record->getKeyName() !== "id" || $record->incrementing !== true) {
            $revisionId = 3;
        }
        $latestRevision = $record->getRevision($revisionId);

        $record->rollback($revisionId, $saveAsRevision);

        $restoredRecord = $record->find($latestRevision->revision_identifier)->first();

        if ($record->getKeyName() === "id" || $record->incrementing === true) {
            $hasDifferent = true;
            foreach ($oldRecord->getFillable() as $key) {
                if ($oldRecord[$key] !== $restoredRecord[$key]) {
                    $hasDifferent = false;
                    break;
                }
            }
            $this->assertEquals(true, $hasDifferent, 'Fillable attribute values do not match');
        }

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
        $record = $this->testModel;
        $oldRecord = clone $record;

        $updateCount = 3;
        for ($i = 0; $i < $updateCount; $i++) {
            $record = $this->testUpdate($record);
        }

        $record->delete();

        $revisionCount = $record->allRevisions()->count();

        $this->assertEquals(0, $revisionCount, 'The revisions are not deleted');
    }
}