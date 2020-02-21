<?php
namespace LuminateOne\RevisionTracking\Tests\Unit;

use LuminateOne\RevisionTracking\Tests\TestCase;
use LuminateOne\RevisionTracking\Tests\Models\GrandParent;

class RevisionTestBulkActions extends TestCase
{
    /**
     * Setup the test
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setupRevisionTable();
    }

    /**
     * Test revision mode all
     *
     * @throws \Exception
     */
    public function testModeAll()
    {
        config(['revision_tracking.mode' => 'all']);

        $this->trackBulkUpdate();
        $this->trackBulkDelete(false);
        $this->trackBulkDelete(true);
    }

    /**
     * Test revision mode single
     *
     * @throws \Exception
     */
    public function testModeSingle()
    {
        config(['revision_tracking.mode' => 'single']);

        $this->trackBulkUpdate();
        $this->trackBulkDelete(false);
        $this->trackBulkDelete(true);
    }

    /**
     * Test bulk update, and delete
     *
     * @throws \ErrorException
     */
    private function trackBulkUpdate()
    {
        $grandparentModel = new GrandParent();
        $grandparentModel->createTable();

        GrandParent::insert([
            ['first_name' => 'created', 'last_name' => 'bbb'],
            ['first_name' => 'created', 'last_name' => 'bbb'],
            ['first_name' => 'created', 'last_name' => 'bbb']
        ]);

        GrandParent::where('first_name', 'created')->update(['first_name' => 'update1']);
        $expected = 0;
        $actual = 0;
        $grandParents = GrandParent::where('first_name', 'update1')->get();
        foreach ($grandParents as $aGrandParent) {
            $actual += $aGrandParent->allRevisions()->count();
        }
        $this->assertEquals($expected, $actual, 'The count of revision when normal update should be '. $expected);

        GrandParent::where('first_name', 'update1')->updateTracked(['first_name' => 'update2']);
        $expected = 3;
        $actual = 0;
        $grandParents = GrandParent::where('first_name', 'update2')->get();
        foreach ($grandParents as $aGrandParent) {
            $actual += $aGrandParent->allRevisions()->count();
        }
        $this->assertEquals($expected, $actual, 'The count of bulk insert revision should be ' . $expected);

    }

    /**
     * Test bulk delete when the remove on delete is turned on
     *
     * @param $deleteRevision
     *
     * @throws \ErrorException
     */
    private function trackBulkDelete($deleteRevision)
    {
        config(['revision_tracking.remove_on_delete' => $deleteRevision]);

        $grandparentModel = new GrandParent();
        $grandparentModel->createTable();

        GrandParent::insert([
            ['first_name' => 'created1', 'last_name' => 'bbb'],
            ['first_name' => 'created1', 'last_name' => 'bbb'],
            ['first_name' => 'created1', 'last_name' => 'bbb']
        ]);
        $grandParents = GrandParent::where('first_name', 'created1')->get();

        GrandParent::where('first_name', 'created1')->updateTracked(['first_name' => 'updated3']);
        GrandParent::where('first_name', 'updated3')->deleteTracked();

        $expected = $deleteRevision ? 0 : 6;
        $actual = 0;
        foreach ($grandParents as $aGrandParent) {
            $actual += $aGrandParent->allRevisions()->count();
        }
        $this->assertEquals($expected, $actual, 'The count of revision should be ' . $expected);
    }
}