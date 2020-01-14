<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Address extends Model
{
    use Revisionable;

    protected $fillable = ['suburb', 'city', 'country', 'user_id'];

    public function user(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\User');
    }

    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create('addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('suburb');
            $table->string('city');
            $table->string('country');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}