<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChildParent extends Model
{
    use Revisionable;

    protected $fillable = ['first_name', 'last_name'];

    public function user(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\GrandParent');
    }

    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('grand_parent_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamps();

            $table->foreign('grand_parent_id')->references('id')->on('grand_parents')->onDelete('cascade');
        });
    }
}