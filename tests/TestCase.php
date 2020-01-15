<?php
namespace LuminateOne\RevisionTracking\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        if(Schema::hasTable('revisions')){
            return;
        }
        // import the CreateRevision class from the migration
        include_once __DIR__ . '/../migrations/create_revisions_versions_table.php';

        // run the up() method of that migration class
        (new \CreateRevisionsVersionsTable)->up();
    }

    protected function getPackageProviders($app)
    {
        return ['LuminateOne\RevisionTracking\Providers\RevisionServiceProvider'];
    }

    /**
     * It will create a new Model
     * Since we are using RefreshDatabase Trait, so it will also create the table for the model
     * and the revision table will be created if the revision mode is set to single
     *
     * @param  string $modelName A model name with namespace
     * @return Model    Return the created model
     */
    public function setupModel($modelName)
    {
        $faker = \Faker\Factory::create();
        $model = new $modelName();
        $model->createTable();

        foreach (($model->getFillable()) as $key) {
            $model[$key] = $faker->name;
        }
        $model->save();

        if ($model->revisionMode() === 'single') {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();

            if(Schema::hasTable($revisionTableName)){
                return $model;
            }

            Schema::create($revisionTableName, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('model_identifier');
                $table->text('original_values');
                $table->timestamps();
            });
        }

        return $model;
    }
}