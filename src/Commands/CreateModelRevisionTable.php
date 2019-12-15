<?php
namespace LuminateOne\RevisionTracking\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelRevisionTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table:revision {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a revision table for a specific Model';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $modelName = $this->argument('model');
        if (!class_exists($modelName)) {
            $this->error("The Model: " . $modelName . ' does not exists, please include the name spaces.');
            return;
        }

        if ($this->confirm('Do you wish to continue?')) {
            $model = new $modelName();

            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();

            if(Schema::hasTable($revisionTableName)){
                $this->error("The revision table '" . $revisionTableName . "' for Model '" . $modelName . "' already exists. Please check the Model name. If you just changed the revision table prefix in config file, please run 'php artisan config:clear'.");
                return;
            }

            Schema::create($revisionTableName, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('revision_identifier');
                $table->text('original_values');
                $table->timestamps();
            });

            $this->info("The revision table '" . $revisionTableName . "' for Model '" . $modelName . "' has been created.");
        }
    }
}
