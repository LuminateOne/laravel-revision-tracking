<?php
namespace LuminateOne\RevisionTracking\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use LuminateOne\RevisionTracking\Traits\Revisionable;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentWithSoftDeletes extends Model
{
    use Revisionable,
        SoftDeletes;

    protected $fillable = ['first_name', 'last_name'];

    /**
     * A parent has many childWithSoftDeletes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childrenWithSoftDeletes(){
        return $this->hasMany('LuminateOne\RevisionTracking\Tests\Models\ChildWithSoftDeletes');
    }

    /**
     * A parent belongs to grandparent
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function grandparent(){
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
            $table->softDeletes();

            $table->foreign('grand_parent_id')->references('id')->on('grand_parents')->onDelete('cascade');
        });
    }
}