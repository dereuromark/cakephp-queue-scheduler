<?php declare(strict_types=1);

namespace QueueScheduler\Scheduler\Lock;

/**
 * Mutex contract for coordinating scheduler invocations.
 *
 * The default implementation is a single-host file lock. Multi-host
 * deployments can plug in their own implementation (DB advisory lock,
 * Redis SETNX, etc.).
 */
interface LockInterface {

	/**
	 * Acquire the lock, blocking up to $timeout seconds.
	 *
	 * @param int $timeout Maximum seconds to wait for the lock.
	 * @return bool True on success, false if timeout elapsed without acquiring.
	 */
	public function acquire(int $timeout): bool;

	/**
	 * Release the lock. Safe to call without a prior successful acquire().
	 *
	 * @return void
	 */
	public function release(): void;

}
