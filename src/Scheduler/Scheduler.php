<?php declare(strict_types=1);

namespace QueueScheduler\Scheduler;

use Cake\Collection\CollectionInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use QueueScheduler\Model\Entity\SchedulerRow;

class Scheduler {

	use LocatorAwareTrait;

	/**
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
	 * @param \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\SchedulerRow> $events
	 *
	 * @return int
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
	 * @param \Cake\Collection\CollectionInterface $rows
	 *
	 * @return \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\SchedulerRow>
	 */
	protected function dueEvents(CollectionInterface $rows): CollectionInterface {
		return $rows->filter(function (SchedulerRow $row) {
			return $row->isDue();
		});
	}

}
