<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cron\CronExpression;
use Exception;
use Locale;
use Panlatent\CronExpressionDescriptor\ExpressionDescriptor;
use QueueScheduler\Command\RunCommand;
use QueueScheduler\Model\Entity\SchedulerRow;
use Throwable;

class QueueSchedulerController extends QueueSchedulerAppController {

	/**
	 * Index method
	 *
	 * @return void Renders view
	 */
	public function index(): void {
		$schedulerRows = $this->fetchTable('QueueScheduler.SchedulerRows')
			->find('active')
			->contain(['LastQueuedJob' => ['fields' => ['id', 'fetched', 'completed']]])
			->all()
			->toArray();

		$runningJobs = $this->fetchTable('Queue.QueuedJobs')->find('queued')
			->where(['reference LIKE' => 'queue-scheduler-%'])
			->all()
			->toArray();
		$runningJobs = Hash::combine($runningJobs, '{n}.reference', '{n}');

		$schedulerStatus = $this->buildSchedulerStatus();

		$this->set(compact('schedulerRows', 'runningJobs', 'schedulerStatus'));
	}

	/**
	 * Read the heartbeat written by RunCommand and decide whether the
	 * scheduler is healthy. Default threshold is 65 seconds: 60s for the
	 * minutely cron interval plus a few seconds of slack for pass duration
	 * and cron jitter (the heartbeat is written at the end of a pass, not
	 * the start). Apps that run cron less often can raise it via
	 * `QueueScheduler.healthyWithinSeconds`.
	 *
	 * @return array{lastTick: int|null, healthy: bool, ageSeconds: int|null, thresholdSeconds: int}
	 */
	protected function buildSchedulerStatus(): array {
		$threshold = (int)(Configure::read('QueueScheduler.healthyWithinSeconds') ?? 65);
		$cacheConfig = (string)(Configure::read('QueueScheduler.cacheConfig') ?? 'default');

		$lastTick = null;
		try {
			$value = Cache::read(RunCommand::HEARTBEAT_KEY, $cacheConfig);
			if (is_int($value)) {
				$lastTick = $value;
			}
		} catch (Throwable) {
			// Cache backend hiccup — treat as "no signal", same as never-run.
		}

		if ($lastTick === null) {
			return [
				'lastTick' => null,
				'healthy' => false,
				'ageSeconds' => null,
				'thresholdSeconds' => $threshold,
			];
		}

		$age = max(0, time() - $lastTick);

		return [
			'lastTick' => $lastTick,
			'healthy' => $age <= $threshold,
			'ageSeconds' => $age,
			'thresholdSeconds' => $threshold,
		];
	}

	/**
	 * Available commands and tasks reference.
	 *
	 * @return void
	 */
	public function available(): void {
	}

	/**
	 * @return void
	 */
	public function intervals(): void {
		if ($this->request->is(['post', 'put'])) {
			$interval = $this->request->getData('interval');
			try {
				$expression = (new CronExpression(SchedulerRow::normalizeCronExpression((string)$interval)))->getExpression();
			} catch (Exception $e) {
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
