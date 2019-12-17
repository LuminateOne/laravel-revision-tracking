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

        $testModelName = $this->getAllModels()[0];

        $this->testModel = new $testModelName();

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
     * @param null $record The newly created model
     * @return $insertedRecord  Clone the created the model.
     */
    public function testUpdate($record = null)
    {
        $faker = \Faker\Factory::create();

        if (!$record) {
            $record = $this->createNewModel();
        }
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

        // Test the values stored in the revision table
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
     * Create a Model for testing
     *
     * @return Model
     */
    public function createNewModel()
    {
        $faker = \Faker\Factory::create();

        $record = new $this->testModel();

        foreach (($record->getFillable()) as $key) {
            $record[$key] = $faker->name;
        }

        $record->save();

        $this->assertTrue($record !== null, "The record has not been inserted");

        return $record;
    }

    /**
     * An array of Models for testing
     *
     * @return array
     */
    private function getAllModels()
    {
        return [
            'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey',
            'LuminateOne\RevisionTracking\TestModels\TableOneUnique',
        ];
    }
}
