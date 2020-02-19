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
        config(['revision_tracking.remove_on_delete' => false]);

        $this->trackBulkActions();
        $this->trackBulkDeleteWhenRemoveOnDeleteIsOn();
    }

    /**
     * Test revision mode single
     *
     * @throws \Exception
     */
    public function testModeSingle()
    {
        config(['revision_tracking.mode' => 'single']);
        config(['revision_tracking.remove_on_delete' => false]);

        $this->trackBulkActions();
        $this->trackBulkDeleteWhenRemoveOnDeleteIsOn();
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
            ['first_name' => 'ddd', 'last_name' => 'bbb'],
            ['first_name' => 'ddd', 'last_name' => 'bbb'],
            ['first_name' => 'ddd', 'last_name' => 'bbb']
        ]);

        GrandParent::where('last_name', 'bbb')->deleteTracked();
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('revisions->original_values->last_name', 'bbb')->count();
        $expected = $grandparentModel->revisionMode() === 'all' ? 3 : 6;
        $this->assertEquals($expected, $actual, 'The count of bulk delete revision should be ' . $expected);

        GrandParent::insert([
            ['first_name' => 'created', 'last_name' => 'bbb'],
            ['first_name' => 'created', 'last_name' => 'bbb'],
            ['first_name' => 'created', 'last_name' => 'bbb']
        ]);

        GrandParent::where('first_name', 'created')->update(['first_name' => 'update1']);
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('revisions->original_values->first_name', 'created')->count();
        $this->assertEquals(0, $actual, 'The count of revision when normal update should be 0');

        GrandParent::where('first_name', 'update1')->updateTracked(['first_name' => 'update2']);
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('revisions->original_values->first_name', 'update1')->count();
        $this->assertEquals(3, $actual, 'The count of bulk insert revision should be 3');
    }

    /**
     * Test bulk delete when the remove on delete is turned on
     *
     * @throws \ErrorException
     */
    private function trackBulkDeleteWhenRemoveOnDeleteIsOn()
    {
        config(['revision_tracking.remove_on_delete' => true]);

        $grandparentModel = new GrandParent();
        $grandparentModel->createTable();

        GrandParent::insert([
            ['first_name' => 'created1', 'last_name' => 'bbb'],
            ['first_name' => 'created1', 'last_name' => 'bbb'],
            ['first_name' => 'created1', 'last_name' => 'bbb']
        ]);

        GrandParent::where('first_name', 'created1')->updateTracked(['first_name' => 'updated2']);
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('revisions->original_values->first_name', 'created1')->count();
        $this->assertEquals(3, $actual, 'The count of bulk insert revision should be 3');

        GrandParent::where('first_name', 'updated2')->deleteTracked();
        $actual = $grandparentModel->getRevisionModel()->newQuery()->where('revisions->original_values->first_name', 'updated')->count();
        $this->assertEquals(0, $actual, 'The count of bulk insert revision should be 0');
    }
}