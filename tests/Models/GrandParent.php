<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use LuminateOne\RevisionTracking\Traits\Revisionable;

class GrandParent extends Model
{
    use Revisionable;

    protected $fillable = ['first_name', 'last_name'];

    public function cParents(){
        return $this->hasMany('LuminateOne\RevisionTracking\Tests\Models\CParent');
    }

    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name');

            $table->timestamps();
        });
    }
}