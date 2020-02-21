<?php
namespace LuminateOne\RevisionTracking\Builder;

use DB;
use LuminateOne\RevisionTracking\RevisionTracking;

class RevisionTrackingBuilder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * Create a revision for each update
     *
     * @param array $newValue
     * @throws \Exception
     */
    public function updateTracked($newValue = [])
    {
        DB::transaction(function () use ($newValue) {
            $modelCollection = $this->get();

            parent::update($newValue);

            $revisions = RevisionTracking::eloquentBulkDiff($modelCollection, $newValue);
            $this->model->getRevisionModel()->insert($revisions);
        });
    }

    /**
     * Create a revision for each delete
     *
     * @throws \Exception
     */
    public function deleteTracked()
    {
        DB::transaction(function () {
            $modelCollection = $this->get();

            parent::delete();

            if (config('revision_tracking.remove_on_delete', true)) {
                $identifiers = [];
                foreach ($modelCollection as $aModel) {
                    array_push($identifiers, $aModel->modelIdentifier(true));
                }
                $this->model->getRevisionModel()->whereIn('model_identifier', $identifiers)->delete();
                return;
            }

            $revisions = RevisionTracking::eloquentBulkDiff($modelCollection, []);
            $this->model->getRevisionModel()->insert($revisions);
        });
    }
}