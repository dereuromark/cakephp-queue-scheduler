<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \QueueScheduler\Controller\Admin\SchedulerRowsController
 */
class SchedulerRowsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.QueueScheduler.SchedulerRows',
	];

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testView(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testAdd(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'add']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testEdit(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'edit', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->disableErrorHandlerMiddleware();

		$this->post(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'run', 1]);

		$this->assertResponseCode(302);

		$queuedJob = $this->fetchTable('Queue.QueuedJobs')->find()->orderByDesc('id')->firstOrFail();
		$this->assertSame('queue-scheduler-1', $queuedJob->reference);
	}

	/**
	 * @return void
	 */
	public function testDisableAll(): void {
		$this->disableErrorHandlerMiddleware();

		$this->post(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'disableAll']);

		$this->assertResponseCode(302);

		$result = $this->fetchTable('QueueScheduler.SchedulerRows')->find('active')->count();
		$this->assertSame(0, $result);
	}

}
