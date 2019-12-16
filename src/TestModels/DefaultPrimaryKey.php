<?php
namespace LuminateOne\RevisionTracking\TestModels;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;

class DefaultPrimaryKey extends Model
{
    use Revisionable;

    protected $fillable = ['name'];
}
