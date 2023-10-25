<?php
declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \QueueScheduler\Controller\Admin\SchedulerRowsController
 */
class SchedulerRowsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var string[]
	 */
	protected $fixtures = [
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
	 * Test index method
	 *
	 * @return void
	 */
	public function testRun(): void {
		$this->disableErrorHandlerMiddleware();

		$this->post(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'run', 1]);

		$this->assertResponseCode(302);

		$queuedJob = TableRegistry::getTableLocator()->get('Queue.QueuedJobs')->find()->orderDesc('id')->firstOrFail();
		$this->assertSame('queue-scheduler-1', $queuedJob->reference);
	}

}
