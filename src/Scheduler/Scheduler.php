<?php

namespace QueueScheduler\Scheduler;

use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use QueueScheduler\Model\Entity\SchedulerRow;
use RuntimeException;

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
		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		/** @var \QueueScheduler\Model\Table\SchedulerRowsTable $rowsTable */
		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');

		$count = 0;
		$events->each(function (SchedulerRow $row) use ($queuedJobsTable, $rowsTable, &$count) {
			if ($row->job_task === null) {
				throw new RuntimeException('Cannot add job task for ' . $row->name);
			}

			$config = $row->job_config;
			$config['reference'] = $row->job_reference;

			if (!$row->allow_concurrent && $queuedJobsTable->isQueued($row->job_reference, $row->job_task)) {
				return;
			}

			$queuedJobsTable->createJob($row->job_task, $row->job_data, $config);
			$row->last_run = new FrozenTime();
			$rowsTable->saveOrFail($row);

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
