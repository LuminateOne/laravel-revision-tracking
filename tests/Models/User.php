<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class User extends Model
{
    use Revisionable;

    protected $fillable = ['first_name', 'last_name'];

    public function addresses(){
        return $this->hasMany('LuminateOne\RevisionTracking\Tests\Models\Address');
    }

    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('first_name');
            $table->string('last_name');

            $table->timestamps();
        });
    }
}