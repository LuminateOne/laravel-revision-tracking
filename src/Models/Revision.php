<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This Model uses the dynamic table to retrieve the revision for different revision mode and different Eloquent model
 *
 * @package LuminateOne\RevisionTracking\Model
 */
class Revision extends Model
{
    protected $fillable = ['model_identifier', 'revisions', 'model_name'];

    /**
     * A function to append the child revision to revisions attributes
     *
     * @param array $value
     */
    public function addChildRevision($value){
        $revision = $this->revisions;

        if(!array_key_exists('child', $revision)){
            $revision['child'] = [];
        }
        array_push($revision['child'], $value);
        $this->revisions = $revision;
        $this->save();
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
    public function getRevisionsAttribute($value)
    {
        return $value ? unserialize($value) : [];
    }

    /**
     * An accessor to retrieve the unserialized original values
     *
     * @return mixed
     */
    public function getOriginalValuesAttribute(){
        $originalValues = null;
        if(array_key_exists('original_values', $this->revisions)){
            $originalValues = $this->revisions['original_values'];
        }
        return $originalValues;
    }

    /**
     * An accessor to retrieve the unserialized child revision
     *
     * @return mixed
     */
    public function getChildRevisionsAttribute(){
        $child = null;
        if(array_key_exists('child', $this->revisions)){
            $child = $this->revisions['child'];
        }
        return $child;
    }

    /**
     * A mutator to serialize original_values
     *
     * @param $value
     * @return void
     */
    public function setOriginalValuesAttribute($value)
    {
        $revisions = $this->revisions;

        $revisions['original_values'] = $value;

        $this->revisions = $revisions;
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
    public function setRevisionsAttribute($value)
    {
        $this->attributes['revisions'] = serialize($value);
    }
}