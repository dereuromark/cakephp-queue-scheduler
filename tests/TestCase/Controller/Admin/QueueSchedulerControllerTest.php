<?php
declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * QueueScheduler\Controller\Admin\TasksController Test Case
 *
 * @uses \QueueScheduler\Controller\Admin\TasksController
 */
class QueueSchedulerControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

}
