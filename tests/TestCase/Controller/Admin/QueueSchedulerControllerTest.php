<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use QueueScheduler\Command\RunCommand;

/**
 * @uses \QueueScheduler\Controller\Admin\QueueSchedulerController
 */
class QueueSchedulerControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Cache::delete(RunCommand::HEARTBEAT_KEY, 'default');
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Cache::delete(RunCommand::HEARTBEAT_KEY, 'default');
		Configure::delete('QueueScheduler.healthyWithinSeconds');
		Configure::delete('QueueScheduler.cacheConfig');
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testIndex(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * No heartbeat written yet → page renders the never-run banner so the
	 * admin can tell whether cron has ever fired the scheduler.
	 *
	 * @return void
	 */
	public function testIndexExposesNeverRunSchedulerStatus(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$status = $this->viewVariable('schedulerStatus');
		$this->assertIsArray($status);
		$this->assertNull($status['lastTick']);
		$this->assertFalse($status['healthy']);
	}

	/**
	 * Fresh heartbeat (within the configured window) → healthy.
	 *
	 * @return void
	 */
	public function testIndexExposesHealthyWhenHeartbeatIsRecent(): void {
		Cache::write(RunCommand::HEARTBEAT_KEY, time() - 10, 'default');
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$status = $this->viewVariable('schedulerStatus');
		$this->assertIsArray($status);
		$this->assertTrue($status['healthy']);
	}

	/**
	 * Stale heartbeat (older than the threshold) → unhealthy. Default
	 * threshold is 65s; this test writes a 5-minute-old timestamp.
	 *
	 * @return void
	 */
	public function testIndexExposesUnhealthyWhenHeartbeatIsStale(): void {
		Cache::write(RunCommand::HEARTBEAT_KEY, time() - 300, 'default');
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$status = $this->viewVariable('schedulerStatus');
		$this->assertIsArray($status);
		$this->assertFalse($status['healthy']);
		$this->assertSame(300, $status['ageSeconds']);
	}

	/**
	 * The threshold must be tunable so deployments running cron less
	 * often than every minute can still report as healthy.
	 *
	 * @return void
	 */
	public function testIndexHonorsHealthyWithinSecondsOverride(): void {
		Configure::write('QueueScheduler.healthyWithinSeconds', 600);
		Cache::write(RunCommand::HEARTBEAT_KEY, time() - 300, 'default');
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$status = $this->viewVariable('schedulerStatus');
		$this->assertIsArray($status);
		$this->assertTrue($status['healthy']);
	}

	/**
	 * @return void
	 */
	public function testIntervals(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'intervals']);

		$this->assertResponseCode(200);
	}

}
