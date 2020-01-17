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

    /**
     * A child belongs to parent
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function childParent(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\ParentWithRevision');
    }

    /**
     * A child belongs to parent2
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function childParent2(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\ParentNoRevision');
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
            $table->bigInteger('parent_with_revision_id')->nullable();
            $table->bigInteger('parent_no_revision_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamps();

            $table->foreign('parent_with_revision_id')->references('id')->on('parent_with_revisions')->onDelete('cascade');
            $table->foreign('parent_no_revision_id')->references('id')->on('parent_no_revisions')->onDelete('cascade');
        });
    }
}