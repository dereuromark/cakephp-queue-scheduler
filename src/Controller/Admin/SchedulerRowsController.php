<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use Cake\Http\Response;
use QueueScheduler\Controller\AppController;

/**
 * Rows Controller
 *
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow> paginate(\Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object = null, array $settings = [])
 * @property \QueueScheduler\Model\Table\SchedulerRowsTable $SchedulerRows
 */
class SchedulerRowsController extends AppController {

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
		$row = $this->SchedulerRows->get($id);

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
		$reference = $row->job_reference;

		// Get all completed jobs for this scheduler to calculate statistics
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

		$this->set(compact('row', 'jobStats', 'recentJobs'));
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
				$this->Flash->success(__('The row has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The row could not be saved. Please, try again.'));
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
				$this->Flash->success(__('The row has been saved.'));

				return $this->redirect(['action' => 'view', $id]);
			}
			$this->Flash->error(__('The row could not be saved. Please, try again.'));
		}
		$this->set(compact('row'));

		return null;
	}

	/**
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function run(?string $id = null): ?Response {
		$this->request->allowMethod(['post']);
		$row = $this->SchedulerRows->get($id);

		if ($this->SchedulerRows->run($row)) {
			$this->Flash->success(__('The job has been added to the queue.'));
		} else {
			$this->Flash->error(__('The job could not be added to the queue.'));
		}

		return $this->redirect($this->referer(['action' => 'view', $id]));
	}

	/**
	 * @return \Cake\Http\Response|null Redirects to index.
	 */
	public function disableAll(): ?Response {
		$this->request->allowMethod(['post']);
		$this->SchedulerRows->updateAll(['enabled' => false], ['enabled' => true]);

		$this->Flash->success(__('All jobs have been disabled'));

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
			$this->Flash->success(__('The row has been deleted.'));
		} else {
			$this->Flash->error(__('The row could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
