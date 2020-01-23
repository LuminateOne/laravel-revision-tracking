<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildWithSoftDeletes extends Model
{
    use Revisionable,
        SoftDeletes;

    protected $fillable = ['first_name', 'last_name'];

    /**
     * A child belongs to parentWithSoftDeletes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentWithSoftDeletes(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\ParentWithSoftDeletes');
    }

    /**
     * Create the table for the model
     */
    public function createTable(){
        if(Schema::hasTable($this->getTable())){
            return;
        }

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_with_soft_deletes_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_with_soft_deletes_id')->references('id')->on('parent_with_soft_deletes')->onDelete('cascade');
        });
    }
}