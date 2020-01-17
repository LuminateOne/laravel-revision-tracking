<?php

namespace LuminateOne\RevisionTracking\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupRevisionTable();
    }

    protected function getPackageProviders($app)
    {
        return ['LuminateOne\RevisionTracking\Providers\RevisionServiceProvider'];
    }

    public function setupRevisionTable()
    {
        if (Schema::hasTable('revisions')) {
            return;
        }

        include_once __DIR__ . '/../migrations/create_revisions_table.php';
        (new \CreateRevisionsTable)->up();
    }

    /**
     * Update a model
     *
     * @param  Model $model
     * @param  integer $count
     *
     * @return Model Return the updated model
     */
    public function updateModel($model, $count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->fillModelWithNewValue($model);
            $model->save();
        }
        return $model;
    }

    public function fillModelWithNewValue($model)
    {
        $faker = \Faker\Factory::create();

        foreach (($model->getFillable()) as $key) {
            $model[$key] = $faker->name;
        }
    }

    /**
     * It will create a new Model
     * Since we are using RefreshDatabase Trait, so it will also create the table for the model
     * and the revision table will be created if the revision mode is set to single
     *
     * @param  string $modelClass A model name with namespace
     * @param  array $foreignKeys
     *
     * @return Model    Return the created model
     */
    public function setupModel($modelClass, $foreignKeys = [])
    {
        $faker = \Faker\Factory::create();

        $model = new $modelClass();
        $model->createTable();

        if ($model->usingRevisionableTrait && $model->revisionMode() === 'single') {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();

            if (!Schema::hasTable($revisionTableName)) {
                Schema::create($revisionTableName, function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->text('model_identifier');
                    $table->text('original_values');
                    $table->timestamps();
                });
            }
        }

        \Log::info(print_r($foreignKeys, true));
        foreach (($model->getFillable()) as $key) {
            $model[$key] = $faker->name;
        }

        foreach ($foreignKeys as $key => $value) {
            $model[$key] = $value;
        }

        $model->save();

        return $model;
    }
}