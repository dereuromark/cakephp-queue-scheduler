<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use Cake\Utility\Hash;
use Cron\CronExpression;
use Locale;
use Panlatent\CronExpressionDescriptor\ExpressionDescriptor;
use QueueScheduler\Controller\AppController;
use QueueScheduler\Model\Entity\SchedulerRow;

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

	/**
	 * @return void
	 */
	public function intervals(): void {

		if ($this->request->is(['post', 'put'])) {
			$interval = $this->request->getData('interval');
			try {
				$expression = (new CronExpression($interval));
			} catch (\Exception $e) {
				$expression = null;
				$this->Flash->error(__('Invalid interval') . ': ' . $e->getMessage());
			}
			$result = null;
			if ($expression && class_exists('Panlatent\CronExpressionDescriptor\ExpressionDescriptor')) {
				$locale = Locale::getDefault();
				$result = (new ExpressionDescriptor($expression, $locale, true))->getDescription();
			}

			$this->set(compact('result'));
		}

		$shortcuts = SchedulerRow::shortcuts();

		$this->set(compact('shortcuts'));
	}

}
