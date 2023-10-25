<?php
declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Table;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use QueueScheduler\Model\Entity\Row;
use QueueScheduler\Model\Table\RowsTable;

/**
 * QueueScheduler\Model\Table\RowsTable Test Case
 */
class RowsTableTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \QueueScheduler\Model\Table\RowsTable
	 */
	protected $Rows;

	/**
	 * Fixtures
	 *
	 * @var array<string>
	 */
	protected $fixtures = [
		'plugin.QueueScheduler.Rows',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$config = $this->getTableLocator()->exists('QueueScheduler.Rows') ? [] : ['className' => RowsTable::class];
		$this->Rows = $this->getTableLocator()->get('QueueScheduler.Rows', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->Rows);

		parent::tearDown();
	}

	/**
	 * Test validationDefault method
	 *
	 * @uses \QueueScheduler\Model\Table\RowsTable::validationDefault()
	 *
	 * @return void
	 */
	public function testInsert(): void {
		$row = $this->Rows->newEntity([
			'name' => 'Example Queue Task',
			'type' => Row::TYPE_QUEUE_TASK,
			'frequency' => '+30seconds',
			'content' => ExampleTask::class,
			'enabled' => true,
		]);

		$row = $this->Rows->saveOrFail($row);
		$this->assertNotEmpty($row->next_run);
		$this->assertSame($row->next_run->getTestNow()->toDateTimeString(), $row->next_run->toDateTimeString());

		$row->last_run = (new FrozenTime())->addDays(1);
		$row->frequency = '+50seconds';
		$row = $this->Rows->saveOrFail($row);

		$this->assertSame($row->next_run->getTestNow()->addDays(1)->addSeconds(50)->toDateTimeString(), $row->next_run->toDateTimeString());

		$row->last_run = (new FrozenTime())->addDays(2);
		$row = $this->Rows->saveOrFail($row);

		$this->assertSame($row->next_run->getTestNow()->addDays(2)->addSeconds(50)->toDateTimeString(), $row->next_run->toDateTimeString());
	}

}
