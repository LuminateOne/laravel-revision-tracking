<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DefaultPrimaryKey extends Model
{
    use Revisionable;

    protected $fillable = ['name', 'name1', 'name2'];

    public function createTable(){
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name');
            $table->text('name1');
            $table->text('name2');

            $table->timestamps();
        });
    }
}