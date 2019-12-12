<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

class SingleRevisionModel extends Model
{
    protected $fillable = ['revision_identifier', 'original_values'];

}