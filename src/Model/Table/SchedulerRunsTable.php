<?php declare(strict_types=1);

namespace QueueScheduler\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use QueueScheduler\Model\Entity\SchedulerRun;

/**
 * Run-history table for SchedulerRows. Populated automatically from
 * Scheduler::run() at dispatch time and updated by the `Queue.Job.completed`
 * + `Queue.Job.failed` event listeners.
 *
 * @method \QueueScheduler\Model\Entity\SchedulerRun newEmptyEntity()
 * @method \QueueScheduler\Model\Entity\SchedulerRun newEntity(array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRun get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, ?string $cacheKey = null, array $cacheOptions = []): \Cake\Datasource\EntityInterface
 */
class SchedulerRunsTable extends Table {

	/**
	 * @param array<string, mixed> $config
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('queue_scheduler_runs');
		$this->setEntityClass(SchedulerRun::class);
		$this->addBehavior('Timestamp');

		$this->belongsTo('SchedulerRows', [
			'className' => 'QueueScheduler.SchedulerRows',
			'foreignKey' => 'scheduler_row_id',
			'joinType' => 'INNER',
		]);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 *
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator
			->integer('scheduler_row_id')
			->notEmptyString('scheduler_row_id');

		$validator
			->integer('queued_job_id')
			->allowEmptyString('queued_job_id');

		$validator
			->scalar('status')
			->inList('status', [
				SchedulerRun::STATUS_QUEUED,
				SchedulerRun::STATUS_COMPLETED,
				SchedulerRun::STATUS_FAILED,
				SchedulerRun::STATUS_ABORTED,
			])
			->notEmptyString('status');

		$validator
			->dateTime('dispatched_at')
			->notEmptyDateTime('dispatched_at');

		return $validator;
	}

	/**
	 * Record a fresh dispatch for the given scheduler row. The history
	 * row starts in `queued` status; subsequent event listeners or
	 * manual `markCompleted()` / `markFailed()` calls advance it.
	 *
	 * @param int $schedulerRowId
	 * @param int|null $queuedJobId
	 *
	 * @return \QueueScheduler\Model\Entity\SchedulerRun
	 */
	public function recordDispatch(int $schedulerRowId, ?int $queuedJobId): SchedulerRun {
		$entity = $this->newEntity([
			'scheduler_row_id' => $schedulerRowId,
			'queued_job_id' => $queuedJobId,
			'status' => SchedulerRun::STATUS_QUEUED,
			'dispatched_at' => new DateTime(),
		]);
		$this->saveOrFail($entity);

		return $entity;
	}

	/**
	 * Mark a run as completed. Computes duration_ms from dispatched_at.
	 *
	 * @param int $queuedJobId
	 * @param string $status One of completed/failed/aborted.
	 * @param string|null $failureMessage Captured at resolve time so the
	 *     history survives queued_jobs cleanup.
	 *
	 * @return bool True if a matching run was found and updated.
	 */
	public function markResolved(int $queuedJobId, string $status, ?string $failureMessage = null): bool {
		/** @var \QueueScheduler\Model\Entity\SchedulerRun|null $run */
		$run = $this->find()
			->where(['queued_job_id' => $queuedJobId, 'status' => SchedulerRun::STATUS_QUEUED])
			->orderBy(['id' => 'DESC'])
			->first();
		if ($run === null) {
			return false;
		}

		$now = new DateTime();
		$run->status = $status;
		$run->completed_at = $now;
		$run->duration_ms = max(0, (int)(($now->getTimestamp() - $run->dispatched_at->getTimestamp()) * 1000));
		if ($failureMessage !== null) {
			$run->failure_message = $failureMessage;
		}
		$this->saveOrFail($run);

		return true;
	}

	/**
	 * Recent runs for one scheduler row, newest first. Used by the admin
	 * "history" view.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array<string, mixed> $options Expects `scheduler_row_id` and
	 *     optional `limit` (default 50).
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findRecentForRow(SelectQuery $query, array $options = []): SelectQuery {
		$limit = (int)($options['limit'] ?? 50);
		$rowId = $options['scheduler_row_id'] ?? null;
		if ($rowId === null) {
			return $query->where(['1 = 0']);
		}

		return $query
			->where(['scheduler_row_id' => $rowId])
			->orderBy(['dispatched_at' => 'DESC'])
			->limit($limit);
	}

}
