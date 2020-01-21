<?php
namespace LuminateOne\RevisionTracking\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LuminateOne\RevisionTracking\Tests\Models\Child;
use LuminateOne\RevisionTracking\Tests\Models\GrandParent;
use LuminateOne\RevisionTracking\Tests\Models\NoPrimaryKey;
use LuminateOne\RevisionTracking\Tests\Models\ParentNoRevision;
use LuminateOne\RevisionTracking\Tests\Models\CustomPrimaryKey;
use LuminateOne\RevisionTracking\Tests\Models\DefaultPrimarykey;
use LuminateOne\RevisionTracking\Tests\Models\ParentWithRevision;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['LuminateOne\RevisionTracking\Providers\RevisionServiceProvider'];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'yiming_laravel_revision_tracking',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ]);
    }

    /**
     * Create revision tables
     */
    public function setupRevisionTable()
    {
        if (!Schema::hasTable('revisions')) {
            include_once __DIR__ . '/../migrations/create_revisions_table.php';
            (new \CreateRevisionsTable)->up();
        }

        $models = [
            new Child(),
            new CustomPrimaryKey(),
            new DefaultPrimarykey(),
            new GrandParent(),
            new NoPrimaryKey(),
            new ParentNoRevision(),
            new ParentWithRevision()
        ];

        foreach ($models as $aModel) {
            if ($aModel->usingRevisionableTrait) {
                $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $aModel->getTable();
                if (!Schema::hasTable($revisionTableName)) {
                    Schema::create($revisionTableName, function (Blueprint $table) {
                        $table->bigIncrements('id');
                        $table->text('model_identifier');
                        $table->text('revisions')->nullable();
                        $table->timestamps();
                    });
                }
            }
        }
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


    /**
     * Assign the model with new values
     *
     * @param Model $model
     */
    public function fillModelWithNewValue($model)
    {
        $faker = \Faker\Factory::create();

        foreach (($model->getFillable()) as $key) {
            $model[$key] = $faker->name;
        }
    }

    /**
     * Compare the fillable value of two models
     *
     * @param Model $modelA
     * @param Model $modelB
     *
     * @return boolean
     */
    public function compareTwoModel($modelA, $modelB)
    {
        $hasDifferent = false;
        foreach ($modelA->getFillable() as $key) {
            if ($modelB[$key] !== $modelA[$key]) {
                $hasDifferent = true;
                break;
            }
        }
        return $hasDifferent;
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