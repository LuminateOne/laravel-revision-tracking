<?php
namespace LuminateOne\RevisionTracking\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return ['LuminateOne\RevisionTracking\Providers\RevisionServiceProvider'];
    }

    public function setupRevisionTable(){
        if(Schema::hasTable('revisions')){
            return;
        }

        include_once __DIR__ . '/../migrations/create_revisions_table.php';
        (new \CreateRevisionsTable)->up();
    }

    /**
     * Update a model
     *
     * @param  Model    $model
     * @param  integer  $count
     *
     * @return Model Return the updated model
     */
    public function updateModel($model, $count = 1)
    {
        $faker = \Faker\Factory::create();

        for($i = 0; $i < $count; $i ++){
            foreach (($model->getFillable()) as $key) {
                $model[$key] = $faker->name;
            }
            $model->save();
        }
        return $model;
    }

    /**
     * It will create a new Model
     * Since we are using RefreshDatabase Trait, so it will also create the table for the model
     * and the revision table will be created if the revision mode is set to single
     *
     * @param  string $modelClass A model name with namespace
     * @return Model    Return the created model
     */
    public function setupModel($modelClass)
    {
        $faker = \Faker\Factory::create();
        $model = new $modelClass();
        $model->createTable();

        if ($model->revisionMode() === 'single') {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();

            if(!Schema::hasTable($revisionTableName)) {
                Schema::create($revisionTableName, function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->text('model_identifier');
                    $table->text('original_values');
                    $table->timestamps();
                });
            }
        }

        foreach (($model->getFillable()) as $key) {
            $model[$key] = $faker->name;
        }
        $model->save();

        return $model;
    }
}