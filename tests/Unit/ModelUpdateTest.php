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

class ModelUpdateTest extends TestCase
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
    public function testUpdate()
    {
        $faker = \Faker\Factory::create($dataProvider = null);

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
}
