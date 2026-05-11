<?php declare(strict_types=1);

namespace QueueScheduler\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use QueueScheduler\Model\Entity\SchedulerRun;
use Throwable;

/**
 * Pipe Queue plugin job-lifecycle events into our run-history table so
 * the admin "history" view shows accurate per-row completion / failure
 * data even after the underlying queued_jobs row gets pruned by the
 * queue plugin's cleanup task.
 *
 * Registered globally from FileStoragePlugin's bootstrap (... wait,
 * `QueueSchedulerPlugin::bootstrap()`), so it attaches once per worker
 * lifetime and survives every dispatch.
 */
class QueueJobListener implements EventListenerInterface {

	/**
	 * @return array<string, string>
	 */
	public function implementedEvents(): array {
		return [
			'Queue.Job.completed' => 'onCompleted',
			'Queue.Job.failed' => 'onFailed',
			'Queue.Job.maxAttemptsExhausted' => 'onAborted',
		];
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function onCompleted(EventInterface $event): void {
		$this->markResolved($event, SchedulerRun::STATUS_COMPLETED);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function onFailed(EventInterface $event): void {
		$message = $event->getData('failureMessage');
		$this->markResolved(
			$event,
			SchedulerRun::STATUS_FAILED,
			is_string($message) ? $message : null,
		);
	}

	/**
	 * The queue plugin emits `Queue.Job.maxAttemptsExhausted` after the
	 * last retry. We treat this as a separate terminal state from a
	 * single failure so the admin UI can highlight runs that will not
	 * retry on their own.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function onAborted(EventInterface $event): void {
		$message = $event->getData('failureMessage');
		$this->markResolved(
			$event,
			SchedulerRun::STATUS_ABORTED,
			is_string($message) ? $message : null,
		);
	}

	/**
	 * Common path for all three event handlers — look up the run by
	 * queued_job_id, set the terminal status, capture the failure
	 * message if any. Errors are swallowed: a missing run row
	 * (e.g. for jobs queued outside the scheduler) is not a real
	 * problem, and DB errors during resolve shouldn't take down the
	 * worker.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param string $status
	 * @param string|null $failureMessage
	 * @return void
	 */
	protected function markResolved(EventInterface $event, string $status, ?string $failureMessage = null): void {
		$job = $event->getData('job');
		if (!is_object($job) || !isset($job->id)) {
			return;
		}
		try {
			/** @var \QueueScheduler\Model\Table\SchedulerRunsTable $runs */
			$runs = TableRegistry::getTableLocator()->get('QueueScheduler.SchedulerRuns');
			$runs->markResolved((int)$job->id, $status, $failureMessage);
		} catch (Throwable) {
			// Run-history is best-effort; never let it crash the worker.
		}
	}

}
