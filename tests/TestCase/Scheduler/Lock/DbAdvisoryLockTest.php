<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Scheduler\Lock;

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use QueueScheduler\Scheduler\Lock\DbAdvisoryLock;
use RuntimeException;

class DbAdvisoryLockTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRejectsEmptyName(): void {
		$connection = ConnectionManager::get('test');
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('non-empty');

		new DbAdvisoryLock($connection, '');
	}

	/**
	 * @return void
	 */
	public function testRejectsOverlongName(): void {
		$connection = ConnectionManager::get('test');
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('64 characters');

		new DbAdvisoryLock($connection, str_repeat('x', 65));
	}

	/**
	 * SQLite has no advisory-lock primitive across processes — the impl
	 * must reject that driver at acquire-time rather than silently
	 * succeeding (which would imply protection that doesn't exist).
	 *
	 * @return void
	 */
	public function testRejectsUnsupportedDriver(): void {
		$connection = ConnectionManager::get('test');
		if (!$connection->getDriver() instanceof Sqlite) {
			$this->markTestSkipped('Driver-rejection test only runs on SQLite.');
		}

		$lock = new DbAdvisoryLock($connection, 'queue_scheduler:test');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('MySQL and PostgreSQL only');

		$lock->acquire(0);
	}

	/**
	 * Acquire + release happy path on a real advisory-lock backend.
	 * Skipped under SQLite (covered separately) and run for real on
	 * MySQL / Postgres CI matrices.
	 *
	 * @return void
	 */
	public function testAcquireAndReleaseOnSupportedDrivers(): void {
		$connection = ConnectionManager::get('test');
		$driver = $connection->getDriver();
		if (!($driver instanceof Mysql || $driver instanceof Postgres)) {
			$this->markTestSkipped('Advisory-lock acquire/release only runs on MySQL or Postgres.');
		}

		$lock = new DbAdvisoryLock($connection, 'queue_scheduler:test_happy_path');
		$this->assertTrue($lock->acquire(1), 'First acquire on an idle name must succeed.');

		$lock->release();

		// After release, the same name can be re-acquired.
		$this->assertTrue($lock->acquire(1), 'Re-acquire after release on the same instance must succeed.');
		$lock->release();
	}

	/**
	 * Double-acquire from the same instance must throw, mirroring the
	 * FileLock contract.
	 *
	 * @return void
	 */
	public function testDoubleAcquireThrows(): void {
		$connection = ConnectionManager::get('test');
		$driver = $connection->getDriver();
		if (!($driver instanceof Mysql || $driver instanceof Postgres)) {
			$this->markTestSkipped('Advisory-lock acquire only runs on MySQL or Postgres.');
		}

		$lock = new DbAdvisoryLock($connection, 'queue_scheduler:test_double');
		$this->assertTrue($lock->acquire(1));

		try {
			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('already acquired');
			$lock->acquire(1);
		} finally {
			$lock->release();
		}
	}

	/**
	 * release() on a never-acquired (or already-released) instance is a
	 * no-op, again mirroring FileLock.
	 *
	 * @return void
	 */
	public function testReleaseWithoutAcquireIsNoop(): void {
		$connection = ConnectionManager::get('test');
		$lock = new DbAdvisoryLock($connection, 'queue_scheduler:test_idle');
		$lock->release();
		$lock->release();
		// Reached without exceptions.
		$this->assertTrue(true);
	}

}
