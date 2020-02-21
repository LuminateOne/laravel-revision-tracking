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

        if(!array_key_exists('child_revisions', $revision)){
            $revision['child_revisions'] = [];
        }
        array_push($revision['child_revisions'], $value);
        $this->revisions = $revision;
        $this->save();
    }

    /**
     * An accessor to retrieve the revision
     *
     * @param $value
     * @return array
     */
    public function getModelIdentifierAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * An accessor to retrieve the revision
     *
     * @param $value
     * @return array
     */
    public function getRevisionsAttribute($value)
    {
        return $value ? unserialize($value) : [];
    }

    /**
     * An accessor to retrieve the original values
     *
     * @return mixed
     */
    public function getOriginalValuesAttribute(){
        return array_key_exists('original_values', $this->revisions) ? $this->revisions['original_values'] : null;
    }

    /**
     * An accessor to retrieve the child revisions
     *
     * @return mixed
     */
    public function getChildRevisionsAttribute(){
        return array_key_exists('child_revisions', $this->revisions) ? $this->revisions['child_revisions'] : null;
    }

    /**
     * A mutator to set original values
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
     * An mutator to set model identifier
     *
     * @param $value
     * @return mixed
     */
    public function setModelIdentifierAttribute($value)
    {
        $this->attributes['model_identifier'] = serialize($value);
    }

    /**
     * An mutator to set revisions
     *
     * @param $value
     * @return mixed
     */
    public function setRevisionsAttribute($value)
    {
        $this->attributes['revisions'] = serialize($value);
    }
}