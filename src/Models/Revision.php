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
    protected $fillable = ['related_revision', 'revision_identifier', 'original_values', 'model_name'];

    /**
     * An accessor to retrieve the unserialized revision_identifier
     *
     * @param $value
     * @return mixed
     */
    public function getRevisionIdentifierAttribute($value)
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
     * An accessor to retrieve the unserialized related_revision
     *
     * @param $value
     * @return mixed
     */
    public function getRelatedRevisionAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * A mutator to serialize revision_identifier
     *
     * @param $value
     * @return void
     */
    public function setRevisionIdentifierAttribute($value)
    {
        $this->attributes['revision_identifier'] = serialize($value);
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
     * A mutator to serialize related_revision
     *
     * @param $value
     * @return void
     */
    public function setRelatedRevisionAttribute($value)
    {
        $this->attributes['related_revision'] = serialize($value);
    }
}