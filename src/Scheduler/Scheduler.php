<?php

namespace QueueScheduler\Scheduler;

use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use QueueScheduler\Model\Entity\Row;
use RuntimeException;

class Scheduler {

	use LocatorAwareTrait;

	/**
	 * @return \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\Row>
	 */
	public function events(): CollectionInterface {
		$events = $this->fetchTable('QueueScheduler.Rows')
			->find('active')
			->find('scheduled')
			->all();

		return $this->dueEvents($events);
	}

	/**
	 * @param \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\Row> $events
	 *
	 * @return int
	 */
	public function schedule(CollectionInterface $events): int {
		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		/** @var \QueueScheduler\Model\Table\RowsTable $rowsTable */
		$rowsTable = $this->fetchTable('QueueScheduler.Rows');

		$count = 0;
		$events->each(function (Row $row) use ($queuedJobsTable, $rowsTable, &$count) {
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
	 * @return \Cake\Collection\CollectionInterface<\QueueScheduler\Model\Entity\Row>
	 */
	protected function dueEvents(CollectionInterface $rows): CollectionInterface {
		return $rows->filter(function (Row $row) {
			return $row->isDue();
		});
	}

}
