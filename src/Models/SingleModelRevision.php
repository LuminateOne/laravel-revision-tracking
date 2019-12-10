<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SingleModelRevision extends Model
{
   protected $fillable = ['revision_identifiers', 'original_values'];


    /**
     *  Create a revision table for this Model
     */
    public function createTableIfNotExists(){

       if(Schema::hasTable($this->table)) return;

       Schema::create($this->table, function (Blueprint $table) {
           $table->bigIncrements('id');
           $table->string('revision_identifiers');
           $table->string('original_values');
           $table->timestamps();
       });
   }
}