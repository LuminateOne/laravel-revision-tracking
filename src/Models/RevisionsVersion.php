<?php
namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionsVersion extends Model
{
    protected $fillable = ['revision_table_name'];
}