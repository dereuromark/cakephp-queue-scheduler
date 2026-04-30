<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Scheduler\Lock;

use Cake\TestSuite\TestCase;
use QueueScheduler\Scheduler\Lock\FileLock;
use RuntimeException;

class FileLockTest extends TestCase {

	protected string $lockPath;

	protected function setUp(): void {
		parent::setUp();
		$this->lockPath = TMP . 'queue_scheduler_test_' . uniqid() . '.lock';
	}

	protected function tearDown(): void {
		if (file_exists($this->lockPath)) {
			unlink($this->lockPath);
		}
		parent::tearDown();
	}

	public function testAcquireSucceedsWhenFree(): void {
		$lock = new FileLock($this->lockPath);

		$this->assertTrue($lock->acquire(1));

		$lock->release();
	}

	/**
	 * Sequential acquires on the same path: first releases, second acquires successfully.
	 */
	public function testAcquireSucceedsAfterPriorRelease(): void {
		$first = new FileLock($this->lockPath);
		$second = new FileLock($this->lockPath);

		$this->assertTrue($first->acquire(1));
		$first->release();

		$this->assertTrue($second->acquire(1));
		$second->release();
	}

	public function testAcquireReturnsFalseAfterTimeout(): void {
		$holderScript = sprintf(
			'$h = fopen(%s, "c"); flock($h, LOCK_EX); usleep(2_000_000);',
			var_export($this->lockPath, true),
		);
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$proc = proc_open(['php', '-r', $holderScript], $descriptors, $pipes);
		$this->assertIsResource($proc);

		// Give the holder a moment to grab the lock.
		usleep(200_000);

		$lock = new FileLock($this->lockPath);
		$start = microtime(true);
		$result = $lock->acquire(1);
		$elapsed = microtime(true) - $start;

		$this->assertFalse($result);
		$this->assertGreaterThanOrEqual(1.0, $elapsed);
		$this->assertLessThan(1.5, $elapsed);

		proc_terminate($proc);
		foreach ($pipes as $pipe) {
			fclose($pipe);
		}
		proc_close($proc);
	}

	public function testReleaseIsIdempotent(): void {
		$lock = new FileLock($this->lockPath);

		// Calling release() before any successful acquire is safe.
		$lock->release();

		// Acquire, release twice in a row.
		$this->assertTrue($lock->acquire(1));
		$lock->release();
		$lock->release();

		$this->addToAssertionCount(1);
	}

	public function testAcquireThrowsWhenAlreadyHeld(): void {
		$lock = new FileLock($this->lockPath);
		$this->assertTrue($lock->acquire(1));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('FileLock already acquired');

		try {
			$lock->acquire(1);
		} finally {
			$lock->release();
		}
	}

}
