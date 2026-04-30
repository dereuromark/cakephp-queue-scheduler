<?php declare(strict_types=1);

namespace QueueScheduler\Scheduler;

use Cake\Collection\CollectionInterface;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use QueueScheduler\Model\Entity\SchedulerRow;
use Throwable;

/**
 * Scheduler - Core scheduling engine for queue-based cron jobs.
 *
 * Retrieves scheduled events that are due and dispatches them to the queue.
 * Used by the RunCommand to process scheduled tasks.
 *
 * @author Mark Scherer
 * @license MIT
 */
class Scheduler {

	use LocatorAwareTrait;
	use LogTrait;

	/**
	 * Number of throwable failures during the most recent schedule() call.
	 *
	 * Held-back rows (allow_concurrent=false + already queued) are not failures
	 * and are not counted here.
	 *
	 * @var int
	 */
	protected int $lastRunFailures = 0;

	/**
	 * Get all active and scheduled events that are currently due.
	 *
	 * @return \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\SchedulerRow>
	 */
	public function events(): CollectionInterface {
		$events = $this->fetchTable('QueueScheduler.SchedulerRows')
			->find('active')
			->find('scheduled')
			->all();

		return $this->dueEvents($events);
	}

	/**
	 * Schedule due events by dispatching them to the queue.
	 *
	 * Failures on individual rows are logged and swallowed so that a single bad
	 * row cannot block the rest of the batch; the caller should compare the
	 * returned count against `$events->count()` to detect partial failures.
	 *
	 * @param \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\SchedulerRow> $events Events to schedule.
	 *
	 * @return int Number of events successfully scheduled.
	 */
	public function schedule(CollectionInterface $events): int {
		/** @var \QueueScheduler\Model\Table\SchedulerRowsTable $rowsTable */
		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');

		$this->lastRunFailures = 0;
		$count = 0;
		$events->each(function (SchedulerRow $row) use ($rowsTable, &$count) {
			try {
				if (!$rowsTable->run($row)) {
					return;
				}
			} catch (Throwable $e) {
				$this->lastRunFailures++;
				$this->log(sprintf(
					'Scheduler: failed to schedule row #%d (%s): %s',
					$row->id,
					$row->name,
					$e->getMessage(),
				), 'error');

				return;
			}

			$count++;
		});

		return $count;
	}

	/**
	 * @return int Number of rows that threw during the most recent schedule() call.
	 */
	public function lastRunFailureCount(): int {
		return $this->lastRunFailures;
	}

	/**
	 * Filter events to only those that are currently due.
	 *
	 * @param \Cake\Collection\CollectionInterface $rows All scheduled rows.
	 *
	 * @return \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\SchedulerRow>
	 */
	protected function dueEvents(CollectionInterface $rows): CollectionInterface {
		return $rows->filter(function (SchedulerRow $row) {
			return $row->isDue();
		});
	}

}
