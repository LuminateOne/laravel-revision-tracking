<?php

namespace LuminateOne\RevisionTracking\Commands;

use Illuminate\Console\Command;
use ErrorException;

class AddRevisionModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revisionable:add {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply the Revisionable to the Model';

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
     * @throws ErrorException
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
            $fileName = $modelName . '.php';

            $this->info("Modifying " . $fileName);

            $data = file_get_contents($fileName);

            $namespaceInUse = preg_match("/(use LuminateOne.RevisionTracking.Traits.Revisionable;)/", $data);
            $revisionableInUse = preg_match("/(use Revisionable;)|(,[ ]?Revisionable)|(Revisionable[ ]?,)/", $data);

            if ($namespaceInUse === 0) {
                $data = preg_replace("/(namespace.+;)/", "$1" . chr(13) . chr(10) .
                    "use LuminateOne\RevisionTracking\Traits\Revisionable;", $data);
                file_put_contents($fileName, $data);
            }

            if ($revisionableInUse === 0) {
                $data = preg_replace([
                    "/(class.+extends.+[\n\r]?{)/i",
                ], "$1" . chr(13) . chr(10) . "use Revisionable;", $data);
            }

            if ($namespaceInUse === 1 && $revisionableInUse === 1) {
                $this->error("The Model: " . $modelName . ' is currently using Revisionable.');
                return;
            }

            file_put_contents($fileName, $data);

            $this->info("Finished modifying " . $fileName);
        }
    }
}
