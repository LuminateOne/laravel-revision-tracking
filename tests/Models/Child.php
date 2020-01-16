<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use LuminateOne\RevisionTracking\Traits\Revisionable;

class Child extends Model
{
    use Revisionable;

    protected $fillable = ['first_name', 'last_name'];

    public function childParent(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\CParent');
    }

    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('c_parent_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamps();

            $table->foreign('c_parent_id')->references('id')->on('c_parents')->onDelete('cascade');
        });
    }
}