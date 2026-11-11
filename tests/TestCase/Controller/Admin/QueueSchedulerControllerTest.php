<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use QueueScheduler\Command\RunCommand;
use QueueScheduler\Test\TestCase\RequestAttributeTrait;
use RuntimeException;

/**
 * @uses \QueueScheduler\Controller\Admin\QueueSchedulerController
 */
class QueueSchedulerControllerTest extends TestCase {

	use IntegrationTestTrait;
	use RequestAttributeTrait;

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
		Configure::delete('QueueScheduler.standalone');
		Configure::delete('Test.redirectScheduler');
		Configure::delete('Test.eventRedirectScheduler');
		Configure::delete('Test.loadAuthorization');
		Configure::delete('Test.authorizationSkipped');
		Configure::delete('Test.denyScheduler');
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
	 * CakePHP's CspMiddleware stores the nonce as `cspScriptNonce`.
	 *
	 * @return void
	 */
	public function testIndexUsesCspScriptNonce(): void {
		$this->disableErrorHandlerMiddleware();
		$this->withRequestAttribute('cspScriptNonce', 'core-nonce');

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('<script nonce="core-nonce" src="https://cdn.jsdelivr.net/npm/bootstrap');
		$this->assertResponseNotRegExp('/<script(?![^>]*nonce=)/');
	}

	/**
	 * A custom middleware may still provide the nonce as `cspNonce`.
	 *
	 * @return void
	 */
	public function testIndexFallsBackToCspNonce(): void {
		$this->disableErrorHandlerMiddleware();
		$this->withRequestAttribute('cspNonce', 'custom-nonce');

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('<script nonce="custom-nonce">');
		$this->assertResponseNotRegExp('/<script(?![^>]*nonce=)/');
	}

	/**
	 * Inline styles and CDN stylesheets need the style nonce under a nonce-only `style-src`.
	 *
	 * @return void
	 */
	public function testIndexUsesCspStyleNonce(): void {
		$this->disableErrorHandlerMiddleware();
		$this->withRequestAttribute('cspStyleNonce', 'style-nonce');

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('<style nonce="style-nonce">');
		$this->assertResponseNotRegExp('/<style(?![^>]*nonce=)/');
		$this->assertResponseNotRegExp('/<link(?![^>]*nonce=)[^>]*stylesheet/');
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
		$this->assertGreaterThanOrEqual(300, $status['ageSeconds']);
		$this->assertLessThan(305, $status['ageSeconds']);
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

	public function testApplicationRedirectIsPreserved(): void {
		Configure::write('Test.redirectScheduler', true);
		$this->get('/admin/queue-scheduler');
		$this->assertRedirect('/login');
		$this->assertResponseCode(302);
	}

	public function testApplicationBeforeFilterCanDenyAccess(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Test.denyScheduler', true);
		$this->expectException(ForbiddenException::class);
		$this->get('/admin/queue-scheduler');
	}

	public function testStandaloneDoesNotCallApplicationBeforeFilter(): void {
		Configure::write('Test.denyScheduler', true);
		Configure::write('QueueScheduler.standalone', true);
		$this->get('/admin/queue-scheduler');
		$this->assertResponseOk();
	}

	public function testAccessGateExceptionPropagates(): void {
		Configure::write('QueueScheduler.adminAccess', function (): bool {
			throw new RuntimeException('Unavailable identity provider');
		});
		$this->disableErrorHandlerMiddleware();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unavailable identity provider');
		try {
			$this->get('/admin/queue-scheduler');
		} finally {
			Configure::write('QueueScheduler.adminAccess', fn () => true);
		}
	}

	public function testApplicationEventRedirectIsPreserved(): void {
		Configure::write('Test.eventRedirectScheduler', true);
		Configure::write('Test.loadAuthorization', true);
		Configure::write('QueueScheduler.adminAccess', fn () => false);
		try {
			$this->get('/admin/queue-scheduler');
			$this->assertRedirect('/login');
			$this->assertTrue(Configure::read('Test.authorizationSkipped'));
		} finally {
			Configure::write('QueueScheduler.adminAccess', fn () => true);
		}
	}

}
