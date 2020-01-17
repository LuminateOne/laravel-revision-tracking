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
    protected $fillable = ['parent_revision', 'child_revisions', 'model_identifier', 'original_values', 'model_name'];

    /**
     * A function to create the revision identifier
     *
     * @return mixed
     */
    public function revisionIdentifier()
    {
        return [$this->getKeyName() => $this->getKey(), 'model_name' => $this->attributes['model_name']];
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
     * An accessor to retrieve the unserialized parent_revision
     *
     * @param $value
     * @return mixed
     */
    public function getParentRevisionAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * An accessor to retrieve the unserialized child_revisions
     *
     * @param $value
     * @return mixed
     */
    public function getChildRevisionsAttribute($value)
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

    /**
     * A mutator to serialize parent_revision
     *
     * @param $value
     * @return void
     */
    public function setParentRevisionAttribute($value)
    {
        $this->attributes['parent_revision'] = serialize($value);
    }

    /**
     * A mutator to serialize child_revisions
     *
     * @param $value
     * @return void
     */
    public function setChildRevisionsAttribute($value)
    {
        $this->attributes['child_revisions'] = serialize($value);
    }
}