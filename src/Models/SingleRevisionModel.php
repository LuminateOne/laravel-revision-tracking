<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class SingleRevisionModel extends Model
{
    protected $fillable = ['revision_identifiers', 'original_values'];

    /** An accessor to retrieve the unserialized revision_identifiers
     * @param $value
     * @return mixed
     */
    public function getRevisionIdentifiersAttribute($value){
        return unserialize($value);
    }

    /** An accessor to retrieve the unserialized original_values
     * @param $value
     * @return mixed
     */
    public function getOriginalValuesAttribute($value){
        return unserialize($value);
    }

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