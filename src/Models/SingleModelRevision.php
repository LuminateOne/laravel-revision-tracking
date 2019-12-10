<?php

namespace LuminateOne\RevisionTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SingleModelRevision extends Model
{
    protected $fillable = ['revision_identifiers', 'original_values', 'revisions_version_id'];


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