<?php

namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LuminateOne\RevisionTracking\Models\RevisionsVersion;

class SingleModelRevision extends Model
{
    protected $fillable = ['revision_identifiers', 'original_values', 'revisions_version_id'];

    // public static function boot()
    // {
    //     parent::boot();
    //     // Hook before creating to serialize the revision identifiers and original values
    //     self::creating(function($model){
    //         $model->revision_identifiers = serialize($model->revision_identifiers);
    //         $model->original_values = serialize($model->original_values);
    //     });
    // }

    /** An mutator to serialize revision_identifiers
     * @param $value
     * @return mixed
     */
    public function setRevisionIdentifiersAttribute($value)
    {
        return $this->attributes['revision_identifiers'] = serialize($value);
    }

    /**
     * An mutator to serialize original_values
     * @param $value
     * @return mixed
     */
    public function setOriginalValuesAttribute($value)
    {
        return $this->attributes['original_values'] = serialize($value);
    }

    /** An accessor for retrieve the unserialized revision_identifiers
     * @param $value
     * @return mixed
     */
    public function getRevisionIdentifiersAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * An accessor for retrieve the unserialized original_values
     * @param $value
     * @return mixed
     */
    public function getOriginalValuesAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function revisionVersion(){
        return $this->belongsTo(RevisionsVersion::class, 'revisions_version_id');
    }

    /**
     *  Create a revision table for this Model
     */
    public function createTableIfNotExists()
    {
        if (Schema::hasTable($this->table)) return;

        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('revisions_version_id');
            $table->text('revision_identifiers');
            $table->text('original_values');
            $table->timestamps();

            $table->foreign('revisions_version_id')->references('id')->on('revisions_versions');
        });
    }
}