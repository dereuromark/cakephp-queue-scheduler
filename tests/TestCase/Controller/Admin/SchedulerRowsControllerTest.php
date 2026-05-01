<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
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
	 * Without a configured QueueScheduler.adminAccess gate, the backend must
	 * fail closed (403). The test bootstrap installs a permissive default;
	 * we delete it for this test only.
	 *
	 * @return void
	 */
	public function testAdminAccessGateUnconfiguredYields403(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::delete('QueueScheduler.adminAccess');

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'index']);
	}

	/**
	 * A non-Closure value (e.g. someone setting a string by mistake) is also
	 * treated as unconfigured.
	 *
	 * @return void
	 */
	public function testAdminAccessGateNonClosureYields403(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('QueueScheduler.adminAccess', 'not a closure');

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'index']);
	}

	/**
	 * A gate that returns false rejects the request.
	 *
	 * @return void
	 */
	public function testAdminAccessGateFalseYields403(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('QueueScheduler.adminAccess', fn () => false);

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'index']);
	}

	/**
	 * Anything other than literal true is rejected (no truthy-coercion).
	 *
	 * @return void
	 */
	public function testAdminAccessGateRequiresStrictTrue(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('QueueScheduler.adminAccess', fn () => 1);

		$this->expectException(ForbiddenException::class);
		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'index']);
	}

	/**
	 * The gate receives the request, so closures can inspect it (path,
	 * identity attribute, headers, etc.) before deciding.
	 *
	 * @return void
	 */
	public function testAdminAccessGateReceivesRequest(): void {
		$this->disableErrorHandlerMiddleware();

		$received = null;
		Configure::write('QueueScheduler.adminAccess', function ($request) use (&$received): bool {
			$received = $request;

			return true;
		});

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertNotNull($received);
		$this->assertStringContainsString('queue-scheduler', $received->getPath());
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
	 * Param block must render and any HTML inside must be escaped.
	 *
	 * @return void
	 */
	public function testViewRendersParamBlockEscaped(): void {
		$this->disableErrorHandlerMiddleware();

		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');
		$row = $rowsTable->get(1);
		// Fixture row is type=1 (Cake command); validateCakeCommandParam expects a JSON array.
		$row->param = '["<script>alert(1)</script>"]';
		$rowsTable->saveOrFail($row);

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Config');
		$this->assertResponseNotContains('<script>alert(1)</script>');
		$this->assertResponseContains('&lt;script&gt;');
	}

	/**
	 * @return void
	 */
	public function testViewWithJobOutput(): void {
		$this->disableErrorHandlerMiddleware();

		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		$queuedJob = $queuedJobsTable->newEntity([
			'job_task' => 'QueueScheduler.CommandExecute',
			'reference' => 'queue-scheduler-1',
			'output' => 'Command output line 1' . "\n" . 'Command output line 2',
			'created' => '2024-01-01 00:00:00',
			'completed' => '2024-01-01 00:00:05',
		]);
		$queuedJobsTable->saveOrFail($queuedJob);

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Recent Executions');
		$this->assertResponseContains('Show output');
		$this->assertResponseContains('Command output line 1');
	}

	/**
	 * @return void
	 */
	public function testViewWithJobFailure(): void {
		$this->disableErrorHandlerMiddleware();

		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		$queuedJob = $queuedJobsTable->newEntity([
			'job_task' => 'QueueScheduler.CommandExecute',
			'reference' => 'queue-scheduler-1',
			'failure_message' => 'Command failed with exit code 1',
			'created' => '2024-01-01 00:00:00',
			'completed' => '2024-01-01 00:00:05',
		]);
		$queuedJobsTable->saveOrFail($queuedJob);

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Show error');
		$this->assertResponseContains('Command failed with exit code 1');
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

	/**
	 * @return void
	 */
	public function testRunAllNew(): void {
		$this->disableErrorHandlerMiddleware();

		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');
		$pendingA = $rowsTable->newEntity([
			'name' => 'Pending A',
			'type' => 0,
			'content' => 'Queue\\Queue\\Task\\ExampleTask',
			'frequency' => '+ 30 seconds',
			'enabled' => 1,
			'last_run' => null,
		]);
		$rowsTable->saveOrFail($pendingA);
		$pendingB = $rowsTable->newEntity([
			'name' => 'Pending B',
			'type' => 0,
			'content' => 'Queue\\Queue\\Task\\ExampleTask',
			'frequency' => '+ 30 seconds',
			'enabled' => 1,
			'last_run' => null,
		]);
		$rowsTable->saveOrFail($pendingB);
		$disabled = $rowsTable->newEntity([
			'name' => 'Disabled C',
			'type' => 0,
			'content' => 'Queue\\Queue\\Task\\ExampleTask',
			'frequency' => '+ 30 seconds',
			'enabled' => 0,
			'last_run' => null,
		]);
		$rowsTable->saveOrFail($disabled);

		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		$before = $queuedJobsTable->find()->count();

		$this->post(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'SchedulerRows', 'action' => 'runAllNew']);

		$this->assertResponseCode(302);

		$after = $queuedJobsTable->find()->count();
		$this->assertSame(2, $after - $before, 'Both enabled pending rows should be queued; the disabled one and the already-run fixture row are skipped.');

		$this->assertNotNull($rowsTable->get($pendingA->id)->last_run);
		$this->assertNotNull($rowsTable->get($pendingB->id)->last_run);
		$this->assertNull($rowsTable->get($disabled->id)->last_run);
	}

	/**
	 * Renders the "Run New" button only when 2+ enabled rows have never run.
	 *
	 * @return void
	 */
	public function testIndexShowsRunNewButtonWhenMultipleNew(): void {
		$this->disableErrorHandlerMiddleware();

		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');
		foreach (['New One', 'New Two'] as $name) {
			$row = $rowsTable->newEntity([
				'name' => $name,
				'type' => 0,
				'content' => 'Queue\\Queue\\Task\\ExampleTask',
				'frequency' => '+ 30 seconds',
				'enabled' => 1,
				'last_run' => null,
			]);
			$rowsTable->saveOrFail($row);
		}

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('runAllNew');
		$this->assertResponseContains('Run New');
	}

	/**
	 * Hides the "Run New" button when fewer than 2 rows have never run.
	 *
	 * @return void
	 */
	public function testIndexHidesRunNewButtonWhenNoneOrOneNew(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'QueueScheduler', 'controller' => 'QueueScheduler', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseNotContains('runAllNew');
	}

}
