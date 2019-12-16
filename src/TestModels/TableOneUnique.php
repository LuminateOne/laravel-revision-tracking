<?php
namespace LuminateOne\RevisionTracking\TestModels;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;

class TableOneUnique extends Model
{
    use Revisionable;

    /**
     * primaryKey
     *
     * @var integer
     * @access protected
     */
    protected $primaryKey = 'name';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = ['name', 'name1', 'name2', 'name3', 'name4', 'name5'];
}
