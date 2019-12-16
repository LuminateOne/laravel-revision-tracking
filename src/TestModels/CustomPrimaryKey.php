<?php
namespace LuminateOne\RevisionTracking\TestModels;

use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Eloquent\Model;

class CustomPrimaryKey extends Model
{
    use Revisionable;

    protected $primaryKey = 'name1';

    public $incrementing = false;

    protected $fillable = ['name1', 'name2'];
}
