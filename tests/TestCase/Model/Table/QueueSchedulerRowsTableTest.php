<?php
declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use QueueScheduler\Model\Table\QueueSchedulerRowsTable;

/**
 * QueueScheduler\Model\Table\QueueSchedulerRowsTable Test Case
 */
class QueueSchedulerRowsTableTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \QueueScheduler\Model\Table\QueueSchedulerRowsTable
	 */
	protected $QueueSchedulerRows;

	/**
	 * Fixtures
	 *
	 * @var array<string>
	 */
	protected $fixtures = [
		'plugin.QueueScheduler.QueueSchedulerRows',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$config = $this->getTableLocator()->exists('QueueSchedulerRows') ? [] : ['className' => QueueSchedulerRowsTable::class];
		$this->QueueSchedulerRows = $this->getTableLocator()->get('QueueSchedulerRows', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->QueueSchedulerRows);

		parent::tearDown();
	}

	/**
	 * Test validationDefault method
	 *
	 * @uses \QueueScheduler\Model\Table\QueueSchedulerRowsTable::validationDefault()
	 *
	 * @return void
	 */
	public function testValidationDefault(): void {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
