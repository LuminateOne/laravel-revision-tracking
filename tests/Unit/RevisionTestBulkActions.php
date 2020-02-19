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
        config(['revision_tracking.remove_on_delete' => false]);
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

        $this->trackBulkActions();
    }

    /**
     * Test revision mode single
     *
     * @throws \Exception
     */
    public function testModeSingle()
    {
        config(['revision_tracking.mode' => 'single']);

        $this->trackBulkActions();
    }


    /**
     * Test bulk update, and delete
     *
     * @throws \ErrorException
     */
    private function trackBulkActions()
    {
        $grandparentModel = new GrandParent();
        $grandparentModel->createTable();

        GrandParent::insert([
            ['first_name' => 'aaa1', 'last_name' => 'bbb'],
            ['first_name' => 'aaa2', 'last_name' => 'bbb'],
            ['first_name' => 'aaa3', 'last_name' => 'bbb']
        ]);

        $oldCount = $grandparentModel->getRevisionModel()->newQuery()->where('id', '!=', '')->count();
        GrandParent::where('last_name', 'bbb')->deleteTracked();
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('id', '!=', '')->count();
        $this->assertEquals($grandparentModel->revisionMode() === 'all' ? 3 : 6,
            $actual - $oldCount, 'The count of bulk delete revision should be 3');

        GrandParent::insert([
            ['first_name' => 'ddd', 'last_name' => 'bbb'],
            ['first_name' => 'ddd', 'last_name' => 'bbb'],
            ['first_name' => 'ddd', 'last_name' => 'bbb']
        ]);

        $oldCount = $grandparentModel->getRevisionModel()->newQuery()->where('id', '!=', '')->count();
        GrandParent::where('first_name', 'ddd')->update(['first_name' => 'ccc']);
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('id', '!=', '')->count();
        $this->assertEquals(0, $actual - $oldCount, 'The count of revision when normal update should be 0');

        $oldCount = $grandparentModel->getRevisionModel()->newQuery()->where('id', '!=', '')->count();
        GrandParent::where('first_name', 'ccc')->updateTracked(['first_name' => '111']);
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('id', '!=', '')->count();
        $this->assertEquals(3, $actual - $oldCount, 'The count of bulk insert revision should be 3');
    }
}