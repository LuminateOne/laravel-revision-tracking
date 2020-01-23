<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\SoftDeletes;
use LuminateOne\RevisionTracking\Traits\Revisionable;

class DefaultPrimaryKeyWithSoftDelete extends Model
{
    use Revisionable,
        SoftDeletes;

    protected $fillable = ['name', 'name1', 'name2'];

    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name');
            $table->text('name1');
            $table->text('name2');

            $table->timestamps();
            $table->softDeletes();
        });
    }
}