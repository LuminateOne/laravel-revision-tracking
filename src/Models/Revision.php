<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The Model use the dynamic table to retrieve the revision for different revision Mode and different Eloquent Model
 * Class Revision
 * @package LuminateOne\RevisionTracking\TestModels
 */
class Revision extends Model
{
    protected $fillable = ['revision_identifier', 'original_values', 'model_name'];

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
}