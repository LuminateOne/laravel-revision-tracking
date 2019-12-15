<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The Model use the dynamic table to retrieve the revision for different revision Mode and different Eloquent Model
 * Class RevisionModel
 * @package LuminateOne\RevisionTracking\Models
 */
class RevisionModel extends Model
{
    protected $fillable = ['revision_identifier', 'original_values', 'model_name'];

}