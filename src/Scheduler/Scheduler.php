<?php declare(strict_types=1);

namespace QueueScheduler\Scheduler;

use Cake\Collection\CollectionInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use QueueScheduler\Model\Entity\SchedulerRow;

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
	 * @param \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\SchedulerRow> $events Events to schedule.
	 *
	 * @return int Number of events scheduled.
	 */
	public function schedule(CollectionInterface $events): int {
		/** @var \QueueScheduler\Model\Table\SchedulerRowsTable $rowsTable */
		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');

		$count = 0;
		$events->each(function (SchedulerRow $row) use ($rowsTable, &$count) {
			if (!$rowsTable->run($row)) {
				return;
			}

			$count++;
		});

		return $count;
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
