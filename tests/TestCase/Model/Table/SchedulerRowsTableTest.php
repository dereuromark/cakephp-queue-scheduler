<?php
declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Table;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;

/**
 * QueueScheduler\Model\Table\RowsTable Test Case
 */
class SchedulerRowsTableTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \QueueScheduler\Model\Table\SchedulerRowsTable
	 */
	protected $SchedulerRows;

	/**
	 * Fixtures
	 *
	 * @var array<string>
	 */
	protected $fixtures = [
		'plugin.QueueScheduler.SchedulerRows',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$config = $this->getTableLocator()->exists('QueueScheduler.Scheduler') ? [] : ['className' => SchedulerRowsTable::class];
		$this->SchedulerRows = $this->getTableLocator()->get('QueueScheduler.Scheduler', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->SchedulerRows);

		parent::tearDown();
	}

	/**
	 * Test validationDefault method
	 *
	 *@uses \QueueScheduler\Model\Table\SchedulerRowsTable::validationDefault()
	 *
	 * @return void
	 */
	public function testInsert(): void {
		$row = $this->SchedulerRows->newEntity([
			'name' => 'Example Queue Task',
			'type' => SchedulerRow::TYPE_QUEUE_TASK,
			'frequency' => '+30seconds',
			'content' => ExampleTask::class,
			'enabled' => true,
		]);

		$row = $this->SchedulerRows->saveOrFail($row);
		$this->assertNotEmpty($row->next_run);
		$this->assertSame($row->next_run->getTestNow()->toDateTimeString(), $row->next_run->toDateTimeString());

		$row->last_run = (new FrozenTime())->addDays(1);
		$row->frequency = '+50seconds';
		$row = $this->SchedulerRows->saveOrFail($row);

		$this->assertSame($row->next_run->getTestNow()->addDays(1)->addSeconds(50)->toDateTimeString(), $row->next_run->toDateTimeString());

		$row->last_run = (new FrozenTime())->addDays(2);
		$row = $this->SchedulerRows->saveOrFail($row);

		$this->assertSame($row->next_run->getTestNow()->addDays(2)->addSeconds(50)->toDateTimeString(), $row->next_run->toDateTimeString());
	}

}
