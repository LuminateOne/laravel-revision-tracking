<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomPrimaryKey extends Model
{
    use Revisionable;

    protected $primaryKey = "name";

    public $incrementing = false;

    protected $fillable = ['name', 'name1', 'name2', 'name3', 'name4', 'name5'];

    public function createTable(){
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->string('name')->unique();
            $table->string('name1');
            $table->string('name2');
            $table->string('name3');
            $table->string('name4');
            $table->string('name5');

            $table->timestamps();
        });
    }
}