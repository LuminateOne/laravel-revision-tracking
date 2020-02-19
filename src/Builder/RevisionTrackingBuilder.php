<?php
namespace LuminateOne\RevisionTracking\Builder;

use DB;

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

            foreach ($modelCollection as $aModel) {
                $aModel->update($newValue);
                DB::commit();
            }
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

            foreach ($modelCollection as $aModel) {
                $aModel->delete();
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}