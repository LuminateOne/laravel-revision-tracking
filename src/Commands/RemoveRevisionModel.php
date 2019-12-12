<?php

namespace LuminateOne\RevisionTracking\Commands;

use Illuminate\Console\Command;
use Storage;

class RemoveRevisionModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revisionable:remove {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the Revisionable from a Model';

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
     *
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

            $newContent = preg_replace([
                "/([\n\r]?use LuminateOne.RevisionTracking.Traits.Revisionable;[\n\r]*)/",
                "/([\n\r]*?use Revisionable;[\n\r]*)/",
                "/(,[ ]?Revisionable)/",
                "/(Revisionable[ ]?,)/"
            ], "", $data);

            if($data === $newContent){
                $this->error("The Model: " . $modelName . ' is not using Revisionable.');
                return;
            }

            file_put_contents($fileName, $newContent);

            $this->info("Finished modifying " . $fileName);
        }
    }
}
