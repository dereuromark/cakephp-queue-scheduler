<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Command;

use Cake\Cache\Cache;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use QueueScheduler\Command\RunCommand;
use ReflectionMethod;

/**
 * QueueScheduler\Command\RunCommand Test Case
 *
 * @uses \QueueScheduler\Command\RunCommand
 */
class RunCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.QueueScheduler.SchedulerRows',
	];

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->exec('scheduler run');

		$this->assertExitCode(0);
		$this->assertOutputContains('1 events due for scheduling');
		$this->assertOutputContains('Done: 1 events scheduled');
	}

	/**
	 * --dry-run should list events without enqueueing them or updating last_run.
	 *
	 * @return void
	 */
	public function testRunDryRun(): void {
		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');
		$beforeLastRun = $rowsTable->get(1)->last_run;

		$this->exec('scheduler run --dry-run');

		$this->assertExitCode(0);
		$this->assertOutputContains('would dispatch row #1');
		$this->assertOutputContains('Dry run: 1 events would have been scheduled.');
		$this->assertOutputNotContains('Done:');

		// last_run untouched.
		$this->assertEquals($beforeLastRun, $rowsTable->get(1)->last_run);
	}

	/**
	 * --limit caps the number of events dispatched.
	 *
	 * @return void
	 */
	public function testRunWithLimit(): void {
		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');
		// Add a second due row so the cap actually kicks in.
		$extra = $rowsTable->newEntity([
			'name' => 'Second due row',
			'type' => 1,
			'content' => 'Cake\\Command\\CacheClearCommand',
			'frequency' => '+30seconds',
			'enabled' => true,
		]);
		// Backdate next_run so the row is due now.
		$extra->next_run = (new DateTime())->subSeconds(60);
		$rowsTable->saveOrFail($extra);

		$this->exec('scheduler run --limit=1 --dry-run');

		$this->assertExitCode(0);
		$this->assertOutputContains('2 events due; capping to 1 (--limit)');
		$this->assertOutputContains('Dry run: 1 events would have been scheduled.');
	}

	public function testLoopFlagsRequireBothPresent(): void {
		$this->exec('scheduler run --duration=10');

		$this->assertExitError();
		$this->assertErrorContains('--duration requires --interval');
	}

	public function testLoopFlagsRejectIntervalAlone(): void {
		$this->exec('scheduler run --interval=5');

		$this->assertExitError();
		$this->assertErrorContains('--interval requires --duration');
	}

	public function testLoopFlagsRejectIntervalBelowOne(): void {
		$this->exec('scheduler run --duration=10 --interval=0');

		$this->assertExitError();
		$this->assertErrorContains('--interval must be at least 1 second');
	}

	public function testLoopFlagsRejectNonNumericInterval(): void {
		$this->exec('scheduler run --duration=10 --interval=abc');

		$this->assertExitError();
		$this->assertErrorContains('--interval must be a positive integer');
	}

	public function testLoopFlagsRejectDurationBelowInterval(): void {
		$this->exec('scheduler run --duration=5 --interval=10');

		$this->assertExitError();
		$this->assertErrorContains('--duration must be greater than or equal to --interval');
	}

	public function testLoopRunsMultipleIterationsAndLogsSummary(): void {
		// --duration=2 --interval=1 → expect ~2 iterations within ~2 seconds.
		$start = microtime(true);
		$this->exec('scheduler run --duration=2 --interval=1');
		$elapsed = microtime(true) - $start;

		$this->assertExitCode(0);
		$this->assertGreaterThanOrEqual(2.0, $elapsed);
		$this->assertLessThan(4.0, $elapsed);
		// At least 2 iterations recorded in the summary line.
		$this->assertOutputRegExp('/Scheduler loop: [2-9]\d* iterations/');
	}

	public function testComputeEndTimeAutoTargetsNextMinuteBoundary(): void {
		$command = new RunCommand();
		$method = new ReflectionMethod($command, 'computeEndTime');

		$endTime = $method->invoke($command, 'auto');

		$now = microtime(true);
		$nextMinuteBoundary = (floor($now / 60) + 1) * 60;
		// endTime should be 0.5s before the next minute boundary; allow
		// 0.1s slack for the time elapsed between our computation and
		// the assertion.
		$this->assertEqualsWithDelta($nextMinuteBoundary - 0.5, $endTime, 0.1);
	}

	public function testComputeEndTimeFixedDurationAddsToNow(): void {
		$command = new RunCommand();
		$method = new ReflectionMethod($command, 'computeEndTime');

		$before = microtime(true);
		$endTime = $method->invoke($command, '5');
		$after = microtime(true);

		$this->assertGreaterThanOrEqual($before + 5, $endTime);
		$this->assertLessThanOrEqual($after + 5, $endTime);
	}

	public function testLoopWarnsWhenPcntlMissing(): void {
		if (extension_loaded('pcntl')) {
			$this->markTestSkipped('pcntl is loaded; soft-degrade path cannot be exercised here.');
		}

		$this->exec('scheduler run --duration=2 --interval=1');

		$this->assertExitCode(0);
		$this->assertOutputContains('pcntl extension not loaded');
	}

	/**
	 * After a successful pass, the heartbeat key must be present in the
	 * configured cache so the admin UI can surface "scheduler is alive".
	 *
	 * @return void
	 */
	public function testRunWritesHeartbeatToCache(): void {
		Cache::delete(RunCommand::HEARTBEAT_KEY, 'default');

		$before = time();
		$this->exec('scheduler run');
		$after = time();

		$this->assertExitCode(0);
		$lastTick = Cache::read(RunCommand::HEARTBEAT_KEY, 'default');
		$this->assertIsInt($lastTick);
		$this->assertGreaterThanOrEqual($before, $lastTick);
		$this->assertLessThanOrEqual($after, $lastTick);
	}

	/**
	 * The heartbeat must respect the QueueScheduler.cacheConfig override so
	 * apps with a dedicated Redis/Memcached config can route the key there.
	 *
	 * @return void
	 */
	public function testRunHeartbeatHonorsCacheConfigOverride(): void {
		Cache::setConfig('queue_scheduler_test', [
			'className' => 'File',
			'path' => CACHE,
			'prefix' => 'queue_scheduler_test_',
			'duration' => '+5 minutes',
		]);
		Configure::write('QueueScheduler.cacheConfig', 'queue_scheduler_test');

		try {
			Cache::delete(RunCommand::HEARTBEAT_KEY, 'queue_scheduler_test');
			Cache::delete(RunCommand::HEARTBEAT_KEY, 'default');

			$this->exec('scheduler run');

			$this->assertExitCode(0);
			$this->assertIsInt(Cache::read(RunCommand::HEARTBEAT_KEY, 'queue_scheduler_test'));
			$this->assertNull(Cache::read(RunCommand::HEARTBEAT_KEY, 'default'));
		} finally {
			Configure::delete('QueueScheduler.cacheConfig');
			Cache::delete(RunCommand::HEARTBEAT_KEY, 'queue_scheduler_test');
			Cache::drop('queue_scheduler_test');
		}
	}

	/**
	 * --dry-run must not bump the heartbeat — otherwise smoke-testing a row
	 * silently masks a stalled scheduler.
	 *
	 * @return void
	 */
	public function testDryRunDoesNotWriteHeartbeat(): void {
		Cache::delete(RunCommand::HEARTBEAT_KEY, 'default');

		$this->exec('scheduler run --dry-run');

		$this->assertExitCode(0);
		$this->assertNull(Cache::read(RunCommand::HEARTBEAT_KEY, 'default'));
	}

	public function testLoopExitsCleanlyWhenLockHeld(): void {
		// Use a custom lock path under TMP so we can pre-acquire it.
		$lockPath = TMP . 'queue_scheduler_locked_' . uniqid() . '.lock';
		Configure::write('QueueScheduler.lockPath', $lockPath);

		// Acquire the lock from this test process before invoking the command.
		$holder = fopen($lockPath, 'c');
		$this->assertNotFalse($holder);
		$this->assertTrue(flock($holder, LOCK_EX));

		// Override the lock acquire timeout so we don't wait 30s.
		// We do this by writing a Configure key the command honors (added below).
		Configure::write('QueueScheduler.lockAcquireTimeout', 1);

		try {
			$start = microtime(true);
			$this->exec('scheduler run --duration=10 --interval=1');
			$elapsed = microtime(true) - $start;

			$this->assertExitCode(0);
			$this->assertErrorContains('lock acquire timed out');
			$this->assertGreaterThanOrEqual(1.0, $elapsed);
			$this->assertLessThan(2.5, $elapsed);
		} finally {
			flock($holder, LOCK_UN);
			fclose($holder);
			if (file_exists($lockPath)) {
				unlink($lockPath);
			}
			Configure::delete('QueueScheduler.lockPath');
			Configure::delete('QueueScheduler.lockAcquireTimeout');
		}
	}

}
