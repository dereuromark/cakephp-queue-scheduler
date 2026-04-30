<?php declare(strict_types=1);

namespace QueueScheduler\Scheduler\Lock;

use RuntimeException;

/**
 * Single-host scheduler lock backed by PHP's flock().
 *
 * The kernel auto-releases the lock when the owning process exits, so a
 * crashed scheduler does not strand the lock. flock() has no native
 * timeout in PHP, so acquire() polls with LOCK_EX | LOCK_NB.
 *
 * Note: This is single-host only. Two app servers running cron against
 * the same database will each acquire their own local lock and double-
 * schedule. Multi-host deployments should implement a different
 * LockInterface backend.
 */
class FileLock implements LockInterface {

	/**
	 * Polling interval while waiting for the lock, in microseconds.
	 *
	 * @var int
	 */
	protected const POLL_INTERVAL_US = 100_000;

	protected string $path;

	/**
	 * @var resource|null
	 */
	protected $handle;

	public function __construct(string $path) {
		$this->path = $path;
	}

	public function acquire(int $timeout): bool {
		if ($this->handle !== null) {
			throw new RuntimeException('FileLock already acquired; call release() first');
		}

		$handle = fopen($this->path, 'c');
		if ($handle === false) {
			throw new RuntimeException('Cannot open lock file: ' . $this->path);
		}
		$this->handle = $handle;

		$deadline = microtime(true) + $timeout;
		do {
			if (flock($this->handle, LOCK_EX | LOCK_NB)) {
				return true;
			}
			usleep(static::POLL_INTERVAL_US);
		} while (microtime(true) < $deadline);

		fclose($this->handle);
		$this->handle = null;

		return false;
	}

	public function release(): void {
		if ($this->handle === null) {
			return;
		}
		flock($this->handle, LOCK_UN);
		fclose($this->handle);
		$this->handle = null;
	}

}
