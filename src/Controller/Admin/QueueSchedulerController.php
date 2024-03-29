<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use Cake\Utility\Hash;
use QueueScheduler\Controller\AppController;

class QueueSchedulerController extends AppController {

	use LoadHelperTrait;

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadHelpers();
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
		$schedulerRows = $this->fetchTable('QueueScheduler.SchedulerRows')
			->find('active')
			->all()
			->toArray();

		$runningJobs = $this->fetchTable('Queue.QueuedJobs')->find('queued')
			->where(['reference LIKE' => 'queue-scheduler-%'])
			->all()
			->toArray();
		$runningJobs = Hash::combine($runningJobs, '{n}.reference', '{n}');

		$this->set(compact('schedulerRows', 'runningJobs'));
	}

}
