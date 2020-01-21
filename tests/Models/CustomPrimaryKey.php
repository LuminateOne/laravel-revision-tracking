<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use LuminateOne\RevisionTracking\Traits\Revisionable;

class CustomPrimaryKey extends Model
{
    use Revisionable;

    protected $primaryKey = "name";

    public $incrementing = false;

    protected $fillable = ['name', 'name1', 'name2', 'name3', 'name4', 'name5'];

    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->string('name')->unique('unique1');
            $table->string('name1');
            $table->string('name2');
            $table->string('name3');
            $table->string('name4');
            $table->string('name5');

            $table->timestamps();
        });
    }
}