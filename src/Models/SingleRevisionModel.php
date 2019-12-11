<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class SingleRevisionModel extends Model
{
    protected $fillable = ['revision_identifiers', 'original_values', 'model_name'];

    public function createTableIfNotExist(){

        if(Schema::hasTable($this->getTable())) return;

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('revision_identifiers');
            $table->text('original_values');
            $table->timestamps();
        });
    }
}