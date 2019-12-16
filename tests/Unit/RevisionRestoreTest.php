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

class RevisionRestoreTest extends TestCase
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


    public function testRestore()
    {
        $modelUpdateTest = new ModelUpdateTest();

        //Get the fake data
        $dataProvider = $modelUpdateTest->modelProvider(0);
        $modelName = $dataProvider['model'];
        $columns = $dataProvider['columns'];
        $model = new $modelName();

        // Insert and update the Model
        $oldRecord = $modelUpdateTest->testUpdate($dataProvider);

        // Restore the revision
        RevisionTracking::eloquentRestore($modelName);

        $restoredRecord = $model->find($oldRecord->getKey())->first();

        Log::info('restored record: ' . print_r($restoredRecord->getAttributes(), true));

        foreach ($columns as $key => $value) {
            if ($value !== $restoredRecord->getAttributes()[$key]) {
                $this->assertTrue(false, 'name does not match');
            }
        }

        $this->assertTrue(true, 'Restored!!!');
    }
}
