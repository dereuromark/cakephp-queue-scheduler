<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use Cake\Utility\Hash;
use QueueScheduler\Controller\AppController;
use Templating\View\Helper\IconHelper;

class QueueSchedulerController extends AppController {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->viewBuilder()->addHelpers([
			class_exists(IconHelper::class) ? 'Templating.Icon' : 'Tools.Icon',
			'Queue.Queue',
			'Queue.QueueProgress',
		]);
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
