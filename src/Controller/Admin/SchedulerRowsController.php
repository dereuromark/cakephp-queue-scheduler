<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Rows Controller
 *
 * @property \QueueScheduler\Model\Table\SchedulerRowsTable $SchedulerRows
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow> paginate(\Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object = null, array $settings = [])
 */
class SchedulerRowsController extends QueueSchedulerAppController {

	/**
	 * Index method
	 *
	 * @return void Renders view
	 */
	public function index(): void {
		$rows = $this->paginate($this->SchedulerRows);

		$this->set(compact('rows'));
	}

	/**
	 * View method
	 *
	 * @param string|null $id Row id.
	 *
	 * @return void Renders view
	 */
	public function view(?string $id = null): void {
		$row = $this->SchedulerRows->get($id, contain: [
			'LastQueuedJob' => ['fields' => ['id', 'fetched', 'completed', 'failure_message']],
		]);

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		$reference = $row->job_reference;

		// Get all completed jobs for this scheduler to calculate statistics
		/** @var array<\Queue\Model\Entity\QueuedJob> $completedJobs */
		$completedJobs = $queuedJobsTable->find()
			->select(['fetched', 'completed', 'failure_message'])
			->where([
				'reference' => $reference,
				'completed IS NOT' => null,
			])
			->all()
			->toArray();

		// Calculate statistics in PHP for database portability
		$jobStats = $this->calculateJobStats($completedJobs);

		// Get total count including non-completed jobs
		$jobStats['total_runs'] = $queuedJobsTable->find()
			->where(['reference' => $reference])
			->count();

		// Get recent job executions
		$recentJobs = $queuedJobsTable->find()
			->where(['reference' => $reference])
			->orderByDesc('created')
			->limit(10)
			->all()
			->toArray();

		$this->set(['row' => $row, 'jobStats' => $jobStats, 'recentJobs' => $recentJobs]);
	}

	/**
	 * Calculate job statistics from completed jobs.
	 *
	 * @param array<\Queue\Model\Entity\QueuedJob> $completedJobs
	 * @return array<string, mixed>
	 */
	protected function calculateJobStats(array $completedJobs): array {
		$stats = [
			'total_runs' => 0,
			'completed_runs' => 0,
			'failed_runs' => 0,
			'avg_duration' => null,
			'min_duration' => null,
			'max_duration' => null,
		];

		$durations = [];
		foreach ($completedJobs as $job) {
			$stats['completed_runs']++;
			if ($job->failure_message) {
				$stats['failed_runs']++;
			}
			if ($job->fetched && $job->completed) {
				$durations[] = $job->fetched->diffInSeconds($job->completed);
			}
		}

		if ($durations) {
			$stats['avg_duration'] = array_sum($durations) / count($durations);
			$stats['min_duration'] = min($durations);
			$stats['max_duration'] = max($durations);
		}

		return $stats;
	}

	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
	 */
	public function add(): ?Response {
		$row = $this->SchedulerRows->newEmptyEntity();
		if ($this->request->is('post')) {
			$row = $this->SchedulerRows->patchEntity($row, $this->request->getData());
			if ($this->SchedulerRows->save($row)) {
				$this->Flash->success(__d('queue_scheduler', 'The row has been saved.'));

				return $this->redirect(['action' => 'view', $row->id]);
			}
			$this->Flash->error(__d('queue_scheduler', 'The row could not be saved. Please, try again.'));
		} else {
			foreach ($this->request->getQueryParams() as $key => $value) {
				$row->set($key, $value);
			}
		}

		$this->set(compact('row'));

		return null;
	}

	/**
	 * Edit method
	 *
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
	 */
	public function edit(?string $id = null): ?Response {
		$row = $this->SchedulerRows->get($id);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$row = $this->SchedulerRows->patchEntity($row, $this->request->getData());
			if ($this->SchedulerRows->save($row)) {
				$this->Flash->success(__d('queue_scheduler', 'The row has been saved.'));

				return $this->redirect(['action' => 'view', $id]);
			}
			$this->Flash->error(__d('queue_scheduler', 'The row could not be saved. Please, try again.'));
		}
		$this->set(compact('row'));

		return null;
	}

	/**
	 * Trigger a row manually.
	 *
	 * Plain POST: dispatches the row as configured, exactly like a cron
	 * tick. Advances `last_run` / `next_run` and uses the row's stored
	 * `param` and `job_config`.
	 *
	 * POST with `override_param` or `override_job_config` in the body:
	 * dispatches once with the overridden values without touching
	 * `last_run` / `next_run`. Useful for incident-response re-runs
	 * (e.g. "rerun yesterday's batch with a different date range").
	 * The override is logged at info level — see
	 * `SchedulerRowsTable::runOnce()`. Empty strings on either override
	 * field fall back to the row's stored value.
	 *
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null Redirects to index, or JSON when the
	 *                                  request is an XMLHttpRequest / accepts
	 *                                  application/json.
	 */
	public function run(?string $id = null): ?Response {
		$this->request->allowMethod(['post']);
		$row = $this->SchedulerRows->get($id);

		$overrideParam = $this->request->getData('override_param');
		$overrideJobConfig = $this->request->getData('override_job_config');
		$hasOverride = (is_string($overrideParam) && $overrideParam !== '')
			|| (is_string($overrideJobConfig) && $overrideJobConfig !== '');

		if ($hasOverride) {
			$overrides = $this->parseRunOverrides($row, $overrideParam, $overrideJobConfig);
			if (is_string($overrides)) {
				$this->Flash->error($overrides);

				return $this->redirect($this->referer(['action' => 'view', $id]));
			}
			$identity = $this->request->getAttribute('identity');
			if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
				$overrides['triggered_by'] = (string)$identity->getIdentifier();
			}
			$ok = $this->SchedulerRows->runOnce($row, $overrides);
		} else {
			$ok = $this->SchedulerRows->run($row);
		}

		if ($ok) {
			$message = __d('queue_scheduler', 'The job has been added to the queue.');
		} elseif (!$hasOverride && !$row->isWithinWindow(new DateTime())) {
			$message = __d('queue_scheduler', 'The job is outside its configured dispatch window. Use "Run with overrides" for an ad-hoc exception.');
		} else {
			$message = __d('queue_scheduler', 'The job could not be added to the queue.');
		}

		if ($this->request->is(['ajax', 'json'])) {
			return $this->response
				->withType('application/json')
				->withStringBody((string)json_encode(['success' => $ok, 'message' => $message]));
		}

		if ($ok) {
			$this->Flash->success($message);
		} else {
			$this->Flash->error($message);
		}

		return $this->redirect($this->referer(['action' => 'view', $id]));
	}

	/**
	 * Validate + parse the ad-hoc override fields from the run() POST body.
	 *
	 * Re-uses the same JSON-shape validators the save path uses
	 * (`validateParam`, `validateJobConfig`) so an override that wouldn't be
	 * a valid stored value also can't be dispatched ad-hoc. Returns either
	 * the parsed overrides array (success) or an error message string
	 * (failure) so the caller can surface it as a flash.
	 *
	 * @param \QueueScheduler\Model\Entity\SchedulerRow $row
	 * @param mixed $overrideParam Raw POST value for the param override.
	 * @param mixed $overrideJobConfig Raw POST value for the job_config override.
	 *
	 * @return array{job_data?: array<mixed>|null, job_config?: array<string, mixed>|null}|string
	 */
	protected function parseRunOverrides(mixed $row, mixed $overrideParam, mixed $overrideJobConfig): array|string {
		$overrides = [];

		if (is_string($overrideParam) && $overrideParam !== '') {
			$context = ['data' => ['type' => $row->type]];
			$result = $this->SchedulerRows->validateParam($overrideParam, $context);
			if ($result !== true) {
				return is_string($result)
					? __d('queue_scheduler', 'Invalid param override: {0}', $result)
					: __d('queue_scheduler', 'Invalid param override JSON.');
			}
			$decoded = json_decode($overrideParam, true);
			$overrides['job_data'] = is_array($decoded) ? $decoded : null;
		}

		if (is_string($overrideJobConfig) && $overrideJobConfig !== '') {
			if (!$this->SchedulerRows->validateJobConfig($overrideJobConfig, ['data' => []])) {
				return __d('queue_scheduler', 'Invalid job_config override — must be a JSON object with allowed keys (priority, group).');
			}
			$decoded = json_decode($overrideJobConfig, true);
			$overrides['job_config'] = is_array($decoded) ? $decoded : null;
		}

		return $overrides;
	}

	/**
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function disableAll(): ?Response {
		$this->request->allowMethod(['post']);
		$this->SchedulerRows->updateAll(['enabled' => false], ['enabled' => true]);

		$this->Flash->success(__d('queue_scheduler', 'All jobs have been disabled'));

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * Queue all enabled schedules that have never run yet (last_run IS NULL).
	 * Useful after bulk-adding long-interval schedules (weekly/monthly) where
	 * waiting for the first cron tick is impractical.
	 *
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function runAllNew(): ?Response {
		$this->request->allowMethod(['post']);

		/** @var array<\QueueScheduler\Model\Entity\SchedulerRow> $rows */
		$rows = $this->SchedulerRows->find('active')
			->where(['last_run IS' => null])
			->all()
			->toArray();

		$queued = 0;
		$skipped = 0;
		foreach ($rows as $row) {
			if ($this->SchedulerRows->run($row)) {
				$queued++;
			} else {
				$skipped++;
			}
		}

		if ($queued === 0 && $skipped === 0) {
			$this->Flash->success(__d('queue_scheduler', 'No new schedules to run.'));
		} elseif ($skipped === 0) {
			$this->Flash->success(__d('queue_scheduler', 'Queued {0} new schedule(s).', $queued));
		} else {
			$this->Flash->success(__d('queue_scheduler', 'Queued {0} new schedule(s); {1} skipped.', $queued, $skipped));
		}

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * Delete method
	 *
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function delete(?string $id = null): ?Response {
		$this->request->allowMethod(['post', 'delete']);
		$row = $this->SchedulerRows->get($id);
		if ($this->SchedulerRows->delete($row)) {
			$this->Flash->success(__d('queue_scheduler', 'The row has been deleted.'));
		} else {
			$this->Flash->error(__d('queue_scheduler', 'The row could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
