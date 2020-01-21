<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
// use LuminateOne\RevisionTracking\Traits\Revisionable;

class ParentNoRevision extends Model
{
    // use Revisionable;

    protected $fillable = ['first_name', 'last_name'];

    /**
     * A parent belongs to grandparent
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function grandParent(){
        return $this->belongsTo('LuminateOne\RevisionTracking\Tests\Models\GrandParent');
    }

    /**
     * A parent has many children
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(){
        return $this->hasMany('LuminateOne\RevisionTracking\Tests\Models\Child');
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