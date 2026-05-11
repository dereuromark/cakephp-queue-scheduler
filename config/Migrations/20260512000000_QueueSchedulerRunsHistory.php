<?php declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Per-row run history. Each row in `queue_scheduler_runs` records one
 * dispatched-and-resolved tick of a scheduled job: when it was queued
 * onto the worker queue, when the worker reported back completed or
 * failed, how long it took, and the queued_jobs row it points at
 * (for drilling into the actual payload + error message).
 *
 * Replaces the previous "look at queued_jobs.last_queued_job_id" pattern,
 * which only retains the most-recent run — once the queue's cleanup
 * task purges old queued_jobs rows the history is gone.
 */
class QueueSchedulerRunsHistory extends BaseMigration {

	/**
	 * @return void
	 */
	public function change(): void {
		$this->table('queue_scheduler_runs', ['comment' => 'Per-row run history for cakephp-queue-scheduler'])
			->addColumn('scheduler_row_id', 'integer', [
				'null' => false,
				'comment' => 'FK to queue_scheduler_rows.id',
			])
			->addColumn('queued_job_id', 'integer', [
				'null' => true,
				'default' => null,
				'comment' => 'FK to queued_jobs.id (the row in cakephp-queue). '
					. 'Nullable because the queue plugin auto-purges old rows '
					. 'via Queue.cleanuptimeout — when that happens the FK '
					. 'is set NULL on cleanup, but this history row survives.',
			])
			->addColumn('status', 'string', [
				'limit' => 16,
				'null' => false,
				'default' => 'queued',
				'comment' => 'One of: queued, completed, failed, aborted.',
			])
			->addColumn('dispatched_at', 'datetime', [
				'null' => false,
				'comment' => 'When the scheduler tick enqueued the job.',
			])
			->addColumn('completed_at', 'datetime', [
				'null' => true,
				'default' => null,
				'comment' => 'When the worker reported back. Null while still in-flight.',
			])
			->addColumn('duration_ms', 'integer', [
				'null' => true,
				'default' => null,
				'comment' => 'completed_at - dispatched_at in milliseconds.',
			])
			->addColumn('failure_message', 'text', [
				'null' => true,
				'default' => null,
				'comment' => 'Mirror of queued_jobs.failure_message at the time of resolution, '
					. 'preserved so the history survives the queue-plugin cleanup.',
			])
			->addColumn('created', 'datetime', ['null' => false])
			->addColumn('modified', 'datetime', ['null' => false])
			->addIndex(['scheduler_row_id', 'dispatched_at'], ['name' => 'row_dispatched'])
			->addIndex(['queued_job_id'], ['name' => 'queued_job_id'])
			->addIndex(['status'], ['name' => 'status'])
			->create();
	}

}
