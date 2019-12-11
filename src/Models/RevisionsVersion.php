<?php

namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionsVersion extends Model
{
    protected $fillable = ['revision_identifiers', 'original_values', 'model_name'];


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
}