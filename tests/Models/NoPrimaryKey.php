<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NoPrimaryKey extends Model
{
    use Revisionable;
    
    protected $fillable = ['name'];

    public function createTable(){
        Schema::create('default_primary_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name');
            $table->timestamps();
        });
    }
}