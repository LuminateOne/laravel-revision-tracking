<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

class SingleRevisionModel extends Model
{
    protected $fillable = ['revision_identifier', 'original_values'];

    /** An accessor to retrieve the unserialized revision_identifier
     * @param $value
     * @return mixed
     */
    public function getRevisionIdentifierAttribute($value){
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