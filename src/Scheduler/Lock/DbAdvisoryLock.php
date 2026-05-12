<?php declare(strict_types=1);

namespace QueueScheduler\Scheduler\Lock;

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use RuntimeException;

/**
 * Cross-host scheduler lock backed by the database's advisory-lock
 * primitives.
 *
 * Multi-host deployments cannot rely on {@see FileLock} because each
 * host has its own filesystem; this implementation uses session-scoped
 * advisory locks on the shared database so any number of `bin/cake
 * scheduler run` invocations across the fleet coordinate on a single
 * mutex.
 *
 * Drivers supported:
 *
 * - **MySQL / MariaDB** via `GET_LOCK(name, timeout)` — native blocking
 *   acquire with a server-side timeout, no client-side polling.
 * - **PostgreSQL** via `pg_try_advisory_lock(key)` — non-blocking, so
 *   `acquire()` polls until the deadline. The lock name is hashed to a
 *   63-bit positive bigint because Postgres advisory locks key on int.
 *
 * SQLite is single-process by design (no concurrent writers across
 * hosts), so a database lock is meaningless there — use {@see FileLock}
 * instead.
 *
 * Both advisory-lock flavors are tied to the connection session. If
 * the cron process crashes mid-tick the database releases the lock on
 * connection close, so a stuck lock can never strand the scheduler.
 *
 * Wiring (see `config/app.example.php`):
 *
 * ```php
 * Configure::write('QueueScheduler.lock', [
 *     'driver' => 'db',
 *     'connection' => 'default', // optional, defaults to 'default'
 *     'name' => 'queue_scheduler:run', // optional, defaults to this string
 * ]);
 * ```
 */
class DbAdvisoryLock implements LockInterface {

	/**
	 * Polling interval used by drivers without a native blocking acquire,
	 * matching `FileLock` for consistency. Microseconds.
	 *
	 * @var int
	 */
	protected const POLL_INTERVAL_US = 100_000;

	/**
	 * MySQL `GET_LOCK` truncates the name at 64 chars (since 5.7). We keep
	 * the user-visible name shorter than that to leave space for caller-
	 * supplied prefixes.
	 *
	 * @var int
	 */
	protected const MYSQL_NAME_MAX_LENGTH = 64;

	protected Connection $connection;

	protected string $name;

	/**
	 * Tracks whether this instance currently holds the lock. Defensive
	 * against double-acquire and noop-release.
	 *
	 * @var bool
	 */
	protected bool $held = false;

	public function __construct(Connection $connection, string $name) {
		if ($name === '') {
			throw new RuntimeException('DbAdvisoryLock name must be non-empty.');
		}
		if (strlen($name) > static::MYSQL_NAME_MAX_LENGTH) {
			throw new RuntimeException(sprintf(
				'DbAdvisoryLock name must be <= %d characters (MySQL GET_LOCK limit); got %d.',
				static::MYSQL_NAME_MAX_LENGTH,
				strlen($name),
			));
		}

		$this->connection = $connection;
		$this->name = $name;
	}

	/**
	 * @param int $timeout Maximum seconds to wait for the lock.
	 *
	 * @return bool
	 */
	public function acquire(int $timeout): bool {
		if ($this->held) {
			throw new RuntimeException('DbAdvisoryLock already acquired; call release() first');
		}

		$driver = $this->connection->getDriver();

		if ($driver instanceof Mysql) {
			$statement = $this->connection->execute(
				'SELECT GET_LOCK(?, ?)',
				[$this->name, max(0, $timeout)],
			);
			/** @var array<int, mixed>|false $row */
			$row = $statement->fetch('num');
			if (is_array($row) && (int)($row[0] ?? 0) === 1) {
				$this->held = true;

				return true;
			}

			return false;
		}

		if ($driver instanceof Postgres) {
			$key = $this->hashKey();
			$deadline = microtime(true) + $timeout;
			do {
				$statement = $this->connection->execute(
					'SELECT pg_try_advisory_lock(?)',
					[$key],
				);
				/** @var array<int, mixed>|false $row */
				$row = $statement->fetch('num');
				$acquired = is_array($row) && in_array($row[0] ?? false, [true, 't', '1', 1], true);
				if ($acquired) {
					$this->held = true;

					return true;
				}
				usleep(static::POLL_INTERVAL_US);
			} while (microtime(true) < $deadline);

			return false;
		}

		throw new RuntimeException(sprintf(
			'DbAdvisoryLock supports MySQL and PostgreSQL only; got %s. Use FileLock for SQLite single-host setups.',
			$driver::class,
		));
	}

	/**
	 * @return void
	 */
	public function release(): void {
		if (!$this->held) {
			return;
		}

		$driver = $this->connection->getDriver();

		if ($driver instanceof Mysql) {
			$this->connection->execute('SELECT RELEASE_LOCK(?)', [$this->name]);
		} elseif ($driver instanceof Postgres) {
			$this->connection->execute('SELECT pg_advisory_unlock(?)', [$this->hashKey()]);
		}

		$this->held = false;
	}

	/**
	 * Postgres advisory locks key on bigint. Hash the human-readable lock
	 * name into a stable positive 63-bit int so the same lock name yields
	 * the same key across PHP processes.
	 *
	 * @return int
	 */
	protected function hashKey(): int {
		$hex = substr(hash('sha256', $this->name), 0, 15);

		return (int)hexdec($hex) & 0x7FFFFFFFFFFFFFFF;
	}

}
