<?php declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Adds a `consecutive_failures` counter to queue_scheduler_rows.
 *
 * Tracks how many times in a row the row's dispatched job has terminally
 * failed (queue status = aborted) without an intervening success. The
 * scheduler uses it to back off: after `QueueScheduler.maxConsecutiveFailures`
 * the row is disabled instead of re-dispatched, so a permanently-broken task
 * stops piling up failed jobs. A successful (or fresh, non-aborted) dispatch
 * resets it to 0.
 *
 * Defaults to 0 and is unsigned/not-null, so existing rows behave exactly as
 * before (no backoff until the feature is configured).
 */
class QueueSchedulerConsecutiveFailures extends BaseMigration {

	/**
	 * @return void
	 */
	public function change(): void {
		$this->table('queue_scheduler_rows')
			->addColumn('consecutive_failures', 'integer', [
				'default' => 0,
				'null' => false,
				'signed' => false,
				'comment' => 'Number of consecutive terminally-failed (aborted) dispatches without '
					. 'an intervening success. Reset to 0 on a successful/fresh dispatch.',
			])
			->update();
	}

}
