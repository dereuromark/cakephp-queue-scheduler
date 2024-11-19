<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Queue\Command\RunCommand;
use Queue\Queue\Task\ExecuteTask;
use QueueScheduler\View\Helper\SchedulerHelper;

/**
 * QueueScheduler\View\Helper\SchedulerHelper Test Case
 */
class SchedulerHelperTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \QueueScheduler\View\Helper\SchedulerHelper
	 */
	protected $Scheduler;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Queue']);

		$view = new View();
		$this->Scheduler = new SchedulerHelper($view);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->Scheduler);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testAvailableCommands(): void {
		$commands = $this->Scheduler->availableCommands();
		$this->assertNotEmpty($commands);

		$this->assertSame($commands['Queue.Run'], RunCommand::class);
	}

	/**
	 * @return void
	 */
	public function testAvailableQueueTasks(): void {
		$queueTasks = $this->Scheduler->availableQueueTasks();
		$this->assertNotEmpty($queueTasks);

		$this->assertSame($queueTasks['Queue.Execute'], ExecuteTask::class);
	}

}
