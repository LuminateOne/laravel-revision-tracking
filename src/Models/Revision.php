<?php
namespace LuminateOne\RevisionTracking\Models;

use ErrorException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * This Model uses the dynamic table to retrieve the revision for different revision mode and different Eloquent model
 *
 * @package LuminateOne\RevisionTracking\Model
 */
class Revision extends Model
{
    protected $fillable = ['model_identifier', 'original_values', 'model_name'];

    /**
     * A function to create the revision identifier
     *
     * @param boolean $serialize Serialize the revision identifier or not
     *
     * @return mixed
     */
    public function revisionIdentifier($serialize = false)
    {
        $revisionIdentifier = [$this->getKeyName() => $this->getKey()];

        if($serialize){
            return serialize($revisionIdentifier);
        }

        return $revisionIdentifier;
    }

    /**
     * An accessor to retrieve the unserialized model_identifier
     *
     * @param $value
     * @return mixed
     */
    public function getModelIdentifierAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * An accessor to retrieve the unserialized original_values
     *
     * @param $value
     * @return mixed
     */
    public function getOriginalValuesAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * A mutator to serialize model_identifier
     *
     * @param $value
     * @return void
     */
    public function setModelIdentifierAttribute($value)
    {
        $this->attributes['model_identifier'] = serialize($value);
    }

    /**
     * A mutator to serialize original_values
     *
     * @param $value
     * @return void
     */
    public function setOriginalValuesAttribute($value)
    {
        $this->attributes['original_values'] = serialize($value);
    }
}