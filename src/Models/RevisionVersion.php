<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionVersion extends Model
{
    protected $fillable = ['revision_identifier', 'original_values', 'model_name'];
}