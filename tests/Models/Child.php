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
     * A child belongs to parentWithRevision
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentWithRevision(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\ParentWithRevision');
    }

    /**
     * A child belongs to ParentWithoutRevision
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentsWithoutRevisions(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\ParentWithoutRevision');
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
            $table->bigInteger('parent_without_revision_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamps();

            $table->foreign('parent_with_revision_id')->references('id')->on('parent_with_revisions')->onDelete('cascade');
            $table->foreign('parent_without_revision_id')->references('id')->on('parent_without_revisions')->onDelete('cascade');
        });
    }
}