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
        try {
            DB::beginTransaction();

            $modelCollection = $this->get();

            parent::update($newValue);

            $revisions = RevisionTracking::eloquentBulkDiff($modelCollection, $newValue);
            $this->model->getRevisionModel()->insert($revisions);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create a revision for each delete
     *
     * @throws \Exception
     */
    public function deleteTracked()
    {
        try {
            DB::beginTransaction();

            $modelCollection = $this->get();

            parent::delete();

            if (config('revision_tracking.remove_on_delete', true)) {
                $ids = [];
                foreach ($modelCollection as $aModel) {
                    array_push($ids, $aModel->getKey());
                }
                $this->model->getRevisionModel()->whereIn($this->model->getKeyName(), $ids)->delete();
                return;
            }

            $revisions = RevisionTracking::eloquentBulkDiff($modelCollection, []);
            $this->model->getRevisionModel()->insert($revisions);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}