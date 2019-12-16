<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\TableOneUnique;
use LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey;
use LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LuminateOne\RevisionTracking\RevisionTracking;
use Tests\TestCase;
use Faker\Generator as Faker;
use Log;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the fake data for the Test Models
     * @param $index
     * @return mixed
     */
    public function modelProvider()
    {
        $faker = \Faker\Factory::create();

        return [
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\DefaultPrimaryKey',
                'columns' => ['name' => $faker->name]
            ],
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\CustomPrimaryKey',
                'columns' => ['name1' => $faker->name, 'name2' => $faker->name]
            ],
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\TableNoPrimaryKey',
                'columns' => ['name' => $faker->name]
            ],
            [
                'model' => 'LuminateOne\RevisionTracking\TestModels\TableOneUnique',
                'columns' => [
                    'name' => $faker->name,
                    'name1' => $faker->name,
                    'name2' => $faker->name,
                    'name3' => $faker->name,
                    'name4' => $faker->name,
                    'name5' => $faker->name
                ]
            ],
        ];
    }

    /**
     * Test if the updated event can be catched be Revisionable.
     */
    public function testUpdate()
    {
        $faker = \Faker\Factory::create();

        //Get the Model name and columns
        $dataProvider = $this->modelProvider()[2];
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];

        // Create a new Model
        $model = new $modelName();

        //Create a record
        $record = $model->create($columns);

        //Update the record
        foreach ($columns as $key => $value) {
            $record[$key] = $faker->name;
        }
        $record->save();

        //Get the changed value
        $originalValuesChanged = RevisionTracking::eloquentDiff($record);

        // Get the latest revision
        $revisionModel = $record->getRevisionModel();
        $aRevision = $revisionModel->latest('id')->first();

        $identifier = [$record->getKeyName() => $record->getKey()];

        // Check if the revision identifier are equal
        $this->assertEquals($identifier, $aRevision->revision_identifier, 'Identifiers do not match');
    }
}

<?php
namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Faker\Generator as Faker;
use LuminateOne\RevisionTracking\RevisionTracking;
use Tests\TestCase;
use App\Models\TableNoPrimaryKey;
use App\Models\TableOneUnique;
use App\Models\User;
use App\Models\CustomPrimaryKey;
use App\Models\DefaultPrimaryKey;
use Log;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    public function modelProvider($index)
    {
        $faker = \Faker\Factory::create();

        $models = [
            ['model' => 'App\Models\DefaultPrimaryKey', 'columns' => ['name' => $faker->name]],
            ['model' => 'App\Models\CustomPrimaryKey', 'columns' => ['name1' => $faker->name, 'name2' => $faker->name]],
            ['model' => 'App\Models\TableNoPrimaryKey', 'columns' => ['name' => $faker->name]],
            ['model' => 'App\Models\TableOneUnique', 'columns' => ['name' => $faker->name, 'name1' => $faker->name,
                'name2' => $faker->name, 'name3' => $faker->name, 'name4' => $faker->name, 'name5' => $faker->name]],
        ];

        $this->assertTrue(true);

        return $models[$index];
    }

    /**
     */
    public function testUpdate($dataProvider = null)
    {
        $faker = \Faker\Factory::create();

        //Get the Model name and columns
        if(!$dataProvider){
            $dataProvider = $this->modelProvider(0);
        }
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];

        var_dump('Testing Model: ' . $modelName);

        // Create a new Model
        $model = new $modelName();

        //Create a record
        $record = $model->create($columns);
        var_dump('Old record: ' . print_r($record->getAttributes(), true));

        //Update the record
        foreach ($columns as $key => $value) {
            $record[$key] = $faker->name;
        }
        $record->save();

        //Get the changed value
        $originalValuesChanged = RevisionTracking::eloquentDiff($record);

        var_dump('eloquentDiff: ' . print_r($originalValuesChanged, true));
        var_dump('updated record: ' . print_r($record->getAttributes(), true));

        // Get the latest revision
        $revisionModel = $record->getRevisionModel();
        $aRevision = $revisionModel->latest('id')->first();


        $identifier = [$record->getKeyName() => $record->getKey()];
        var_dump('updated record revision identifier: ' . print_r($aRevision->revision_identifier, true));

        // Check if the revision identifier are equal
        $this->assertTrue($aRevision->revision_identifier === $identifier, 'Identifiers not match');

        return $record;
    }


    public function testRestore()
    {
        //Get the fake data
        $dataProvider = $this->modelProvider(0);
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];
        $model = new $modelName();

        // Insert and update the Model
        $oldRecord = $this->testUpdate($dataProvider);

        // Restore the revision
        RevisionTracking::eloquentRestore($modelName);

        $restoredRecord = $model->find($oldRecord->getKey())->first();

        var_dump('restored record: ' . print_r($restoredRecord->getAttributes(), true));

        foreach ($columns as $key => $value) {
            if ($value !== $restoredRecord->getAttributes()[$key]) {
                $this->assertTrue(false, 'name does not match');
            }
        }

        $this->assertTrue(true, 'Restored!!!');
    }
}
