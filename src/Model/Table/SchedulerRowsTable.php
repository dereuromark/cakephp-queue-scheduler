<?php declare(strict_types=1);

namespace QueueScheduler\Model\Table;

use ArrayObject;
use Cake\Console\CommandInterface;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cron\CronExpression;
use DateInterval;
use Exception;
use InvalidArgumentException;
use Queue\Queue\Task;
use QueueScheduler\Model\Entity\SchedulerRow;
use RuntimeException;

/**
 * Rows Model
 *
 * @method \QueueScheduler\Model\Entity\SchedulerRow newEmptyEntity()
 * @method \QueueScheduler\Model\Entity\SchedulerRow newEntity(array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\SchedulerRow> newEntities(array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \QueueScheduler\Model\Entity\SchedulerRow findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\SchedulerRow> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SchedulerRowsTable extends Table {

	/**
	 * @var array<string>
	 */
	public array $scaffoldSkipFields = ['last_run', 'last_queued_job_id'];

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('queue_scheduler_rows');
		$this->setDisplayField('name');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');

		$this->belongsTo('LastQueuedJob', [
			'className' => 'Queue.QueuedJobs',
			'foreignKey' => 'last_queued_job_id',
			'joinType' => 'LEFT',
		]);

		// Stored as JSON text but exposed as array in PHP — Cake handles the
		// encode/decode round-trip transparently. Guarded so that bootstrapping
		// the table against a not-yet-migrated schema (e.g. mid-upgrade, before
		// `migrations migrate` has run) doesn't crash; the cast attaches once
		// the column lands.
		$schema = $this->getSchema();
		if ($schema->hasColumn('job_config')) {
			$schema->setColumnType('job_config', 'json');
		}
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $validator Validator instance.
	 *
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator
			->scalar('name')
			->maxLength('name', 140)
			->requirePresence('name', 'create')
			->notEmptyString('name')
			->add('name', 'unique', [
				'rule' => 'validateUniqueName',
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'This name is already in use.'),
			]);

		$validator
			->notEmptyString('type');

		$validator
			->scalar('content')
			->requirePresence('content', 'create')
			->notEmptyString('content')
			->add('content', 'validateContent', [
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'Content does not match the chosen type. Use a Task class (or "Plugin.Task" alias) for Queue tasks, a Command class (or "Plugin.Command" alias) for Cake commands, or a shell command string.'),
			]);

		$validator
			->scalar('param')
			->allowEmptyString('param')
			->add('param', 'validateParam', [
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'Param must be a JSON object {…} for Queue tasks or a JSON array […] for Cake commands. Shell commands cannot have a param.'),
			]);

		$validator
			->scalar('job_config')
			->allowEmptyString('job_config')
			->add('job_config', 'validateJobConfig', [
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'Job Config must be a JSON object with allowed keys only: priority (int 1-10) and group (string).'),
			]);

		$validator
			->scalar('frequency')
			->maxLength('frequency', 140)
			->requirePresence('frequency', 'create')
			->notEmptyString('frequency')
			->add('frequency', 'validateFrequency', [
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'Must be a cron expression ("0 11 * * *"), an @-shortcut ("@daily", "@minutely"), a relative interval ("+30 seconds"), or an ISO 8601 duration ("P2D").'),
			]);

		$validator
			->dateTime('last_run')
			->allowEmptyDateTime('last_run');

		$validator
			->allowEmptyString('window_start_time')
			->add('window_start_time', 'validateWindowTime', [
				'rule' => 'validateWindowTime',
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'Window start time must be a valid time like 09:00.'),
			]);

		$validator
			->allowEmptyString('window_end_time')
			->add('window_end_time', 'validateWindowTime', [
				'rule' => 'validateWindowTime',
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'Window end time must be a valid time like 18:00.'),
			]);

		$validator
			->allowEmptyString('window_days_of_week')
			->add('window_days_of_week', 'validateWindowDaysOfWeek', [
				'rule' => 'validateWindowDaysOfWeek',
				'provider' => 'table',
				'message' => __d('queue_scheduler', 'Window days must be a comma-separated list of 0-6 values, e.g. "1,2,3,4,5".'),
			]);

		$validator
			->boolean('allow_concurrent')
			->notEmptyString('allow_concurrent');

		return $validator;
	}

	/**
	 * @param mixed $value
	 * @param array $context
	 *
	 * @return string|bool
	 */
	public function validateContent(mixed $value, array $context): string|bool {
		if (!is_string($value) || !$value) {
			return false;
		}

		$data = $context['data'];
		if (!isset($data['type'])) {
			return false;
		}
		$type = (int)$data['type'];
		switch ($type) {
			case SchedulerRow::TYPE_QUEUE_TASK:
				return $this->validateQueueTask($value, $data);
			case SchedulerRow::TYPE_CAKE_COMMAND:
				return $this->validateCakeCommand($value, $data);
			case SchedulerRow::TYPE_SHELL_COMMAND:
				return $this->validateShellCommand($value, $data);
		}

		return false;
	}

	/**
	 * @param mixed $value
	 * @param array $context
	 *
	 * @return string|bool
	 */
	public function validateParam(mixed $value, array $context): string|bool {
		if (!is_string($value)) {
			return false;
		}

		$data = $context['data'];
		if (!isset($data['type'])) {
			return false;
		}
		$type = (int)$data['type'];
		switch ($type) {
			case SchedulerRow::TYPE_QUEUE_TASK:
				return $this->validateQueueTaskParam($value, $data);
			case SchedulerRow::TYPE_CAKE_COMMAND:
				return $this->validateCakeCommandParam($value, $data);
			case SchedulerRow::TYPE_SHELL_COMMAND:
				return $value === '' ? true : __d('queue_scheduler', 'Cannot have separate param data for shell command.');
		}

		return false;
	}

	/**
	 * Validate the JSON-encoded queue config. Must be a JSON object whose keys
	 * are limited to the few that actually do something at scheduling time:
	 *
	 * - priority: int 1-10 (lower runs sooner)
	 * - group: non-empty string (matches `cake queue worker --group=...`)
	 *
	 * Other Queue\Config\JobConfig keys are intentionally not exposed here:
	 * `notBefore` is meaningless for cron-driven dispatch (cron already controls
	 * timing), `reference` is set by the scheduler itself, and `status` is a
	 * runtime field overwritten on the first progress tick.
	 *
	 * Unknown keys are rejected to surface typos like `priorty` instead of
	 * silently dropping them and emitting a dynamic-property deprecation
	 * notice from JobConfig::fromArray().
	 *
	 * @param mixed $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateJobConfig(mixed $value, array $context): bool {
		if (!is_string($value) || $value === '') {
			return false;
		}
		if (!str_starts_with($value, '{') || !str_ends_with($value, '}')) {
			return false;
		}

		$decoded = json_decode($value, true);
		if (!is_array($decoded)) {
			return false;
		}

		foreach ($decoded as $key => $val) {
			if ($key === 'priority') {
				if (!is_int($val) || $val < 1 || $val > 10) {
					return false;
				}

				continue;
			}
			if ($key === 'group') {
				if (!is_string($val) || $val === '') {
					return false;
				}

				continue;
			}

			return false;
		}

		return true;
	}

	/**
	 * @param mixed $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateFrequency(mixed $value, array $context): bool {
		if (!is_string($value) || !$value) {
			return false;
		}

		if (substr($value, 0, 1) === '+') {
			return $this->validateFrequencyAsStringInterval($value);
		}

		if (substr($value, 0, 1) === 'P') {
			return $this->validateFrequencyAsDateInterval($value);
		}

		return $this->validateFrequencyAsCronExpression($value);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 *
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		$this->adjustQueueTask($data);
		$this->adjustCakeCommand($data);
		$this->adjustParam($data);
		$this->adjustWindowFields($data);
	}

	/**
	 * Collapse "no payload" JSON literals to an empty string before validation.
	 *
	 * Both `validateQueueTaskParam` and `validateCakeCommandParam` reject `{}`
	 * and `[]` via `!empty($decoded)`, so a user who explicitly types the empty
	 * literal — meaning "no args / no payload" — would otherwise hit a
	 * confusing validation error. The field already accepts an empty string
	 * for that intent (see `allowEmptyString('param')`), so normalize the
	 * literal to the same shape. Whitespace-only variants like `[ ]`, `{\n}`
	 * are caught via `json_decode` rather than a string compare.
	 *
	 * @param \ArrayObject $data
	 * @return void
	 */
	protected function adjustParam(ArrayObject $data): void {
		if (!isset($data['param']) || !is_string($data['param'])) {
			return;
		}
		if (trim($data['param']) === '') {
			return;
		}

		$decoded = json_decode($data['param'], true);
		if (is_array($decoded) && !$decoded) {
			$data['param'] = '';
		}
	}

	/**
	 * @param \ArrayObject $data
	 * @return void
	 */
	protected function adjustWindowFields(ArrayObject $data): void {
		foreach (['window_start_time', 'window_end_time'] as $field) {
			if (array_key_exists($field, (array)$data) && $data[$field] === '') {
				$data[$field] = null;
			}
		}

		if (!array_key_exists('window_days_of_week', (array)$data)) {
			return;
		}

		$value = $data['window_days_of_week'];
		if (is_array($value)) {
			$value = implode(',', $value);
		}
		if (!is_string($value)) {
			return;
		}

		$rawParts = array_map('trim', explode(',', $value));
		$parts = [];
		foreach ($rawParts as $part) {
			if ($part === '') {
				continue;
			}
			$parts[$part] = $part;
		}
		$parts = array_keys($parts);
		sort($parts);
		if ($parts === ['0', '1', '2', '3', '4', '5', '6']) {
			$data['window_days_of_week'] = null;

			return;
		}

		$data['window_days_of_week'] = $parts ? implode(',', $parts) : null;
	}

	/**
	 * @param \ArrayObject $data
	 * @return void
	 */
	protected function adjustQueueTask(ArrayObject $data): void {
		if (!isset($data['type']) || strlen((string)$data['type']) === 0 || (int)$data['type'] !== SchedulerRow::TYPE_QUEUE_TASK) {
			return;
		}

		if (empty($data['content'])) {
			return;
		}

		if (str_contains($data['content'], '\\')) {
			return;
		}

		$className = App::className($data['content'], 'Queue/Task', 'Task');
		if ($className) {
			$data['content'] = $className;
		}
	}

	/**
	 * @param \ArrayObject $data
	 * @return void
	 */
	protected function adjustCakeCommand(ArrayObject $data): void {
		if (!isset($data['type']) || strlen((string)$data['type']) === 0 || (int)$data['type'] !== SchedulerRow::TYPE_CAKE_COMMAND) {
			return;
		}

		if (empty($data['content'])) {
			return;
		}

		if (str_contains($data['content'], '\\')) {
			return;
		}

		$className = App::className($data['content'], 'Command', 'Command');
		if ($className) {
			$data['content'] = $className;
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \QueueScheduler\Model\Entity\SchedulerRow $entity
	 * @param \ArrayObject $options
	 *
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (
			$entity->next_run === null
			|| $entity->isDirty('frequency')
			|| $entity->isDirty('last_run')
			|| $entity->isDirty('window_start_time')
			|| $entity->isDirty('window_end_time')
			|| $entity->isDirty('window_days_of_week')
		) {
			$entity->next_run = $entity->calculateNextRun();
		}
	}

	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findActive(SelectQuery $query): SelectQuery {
		$conditions = ['enabled' => true];
		$debug = Configure::read('debug');
		if (!$debug && !Configure::read('QueueScheduler.allowRaw')) {
			$conditions['type !='] = SchedulerRow::TYPE_SHELL_COMMAND;
		}

		return $query->where($conditions);
	}

	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findScheduled(SelectQuery $query): SelectQuery {
		return $query->where(['OR' => ['next_run IS' => null, 'next_run <=' => new DateTime()]]);
	}

	/**
	 * @param \QueueScheduler\Model\Entity\SchedulerRow $row
	 *
	 * @return bool
	 */
	public function run(SchedulerRow $row): bool {
		if ($row->job_task === null) {
			throw new RuntimeException('Cannot add job task for ' . $row->name);
		}
		if (!$row->isWithinWindow(new DateTime())) {
			return false;
		}

		// Wrap the whole isQueued → createJob → save chain in a transaction
		// AND guard the row update with a compare-and-swap on `last_run`. Two
		// scheduler ticks landing within the same second on different hosts
		// (or even the same host with parallel cron) would otherwise both
		// observe `isQueued() === false`, both `createJob()`, and both write
		// `last_run`. The `updateAll(..., ['id' => $row->id, 'last_run IS' => $oldLastRun])`
		// makes the second writer a no-op: if zero rows are updated we have
		// lost the race, so we delete the queued job we just inserted and
		// return false. The transaction ensures the createJob → updateAll
		// pair is atomic and rolled back together on failure.
		$config = $row->job_config;
		$config['reference'] = $row->job_reference;
		$oldLastRun = $row->last_run;

		return $this->getConnection()->transactional(function () use ($row, $config, $oldLastRun): bool {
			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
			if (!$row->allow_concurrent && $queuedJobsTable->isQueued($row->job_reference, $row->job_task)) {
				return false;
			}

			$queuedJob = $queuedJobsTable->createJob($row->job_task, $row->job_data, $config);

			$now = new DateTime();
			$updated = $this->updateAll(
				[
					'last_run' => $now,
					'last_queued_job_id' => $queuedJob->id,
				],
				[
					'id' => $row->id,
					'last_run IS' => $oldLastRun,
				],
			);
			if ($updated === 0) {
				// Lost the race: another tick advanced `last_run` between our
				// isQueued() check and the updateAll. Discard the queued job
				// to keep things idempotent for the caller.
				$queuedJobsTable->deleteAll(['id' => $queuedJob->id]);

				return false;
			}

			$row->last_run = $now;
			$row->last_queued_job_id = $queuedJob->id;
			// next_run is recomputed by beforeSave when last_run is dirty; the
			// updateAll bypassed that, so do it explicitly to keep persistent
			// state consistent without re-running the full save pipeline.
			$row->next_run = $row->calculateNextRun();
			$this->updateAll(
				['next_run' => $row->next_run],
				['id' => $row->id],
			);

			return true;
		});
	}

	/**
	 * Ad-hoc dispatch of a row with per-call overrides for the queue payload
	 * and/or queue config. Intended for incident-response "trigger now"
	 * actions from the admin UI: the override fires in addition to the row's
	 * normal schedule rather than replacing it, so `last_run` / `next_run`
	 * are NOT advanced.
	 *
	 * The override map accepts:
	 *
	 * - `job_data` — replacement payload. Shape must match the row type
	 *   (Queue Task → array, Cake Command → list, Shell Command → empty).
	 *   Pass `null` here to keep the row's stored `param`.
	 * - `job_config` — replacement config (`priority`, `group`). Merged on
	 *   top of the row's stored `job_config` so partial overrides work
	 *   without zeroing other keys. Pass `null` to keep stored config.
	 *
	 * Concurrency: respects `allow_concurrent` the same way the cron path
	 * does. An override-dispatch against an in-flight non-concurrent row
	 * returns `false` instead of dual-firing.
	 *
	 * Audit: every override is logged at `info` level with a
	 * `[QueueScheduler.Override]` prefix capturing the row id, the override
	 * payload, and the resulting `queued_jobs.id`. There is intentionally
	 * no override-history table — the queue plugin's `queued_jobs` row
	 * already retains the dispatched payload until cleanup, and the log
	 * captures the "who triggered this" piece that doesn't fit there.
	 *
	 * @param \QueueScheduler\Model\Entity\SchedulerRow $row
	 * @param array{job_data?: array<mixed>|null, job_config?: array<string, mixed>|null, triggered_by?: string|null} $overrides
	 *
	 * @return bool True if the job was enqueued; false if blocked by
	 *     `allow_concurrent` against an in-flight job.
	 */
	public function runOnce(SchedulerRow $row, array $overrides = []): bool {
		if ($row->job_task === null) {
			throw new RuntimeException('Cannot add job task for ' . $row->name);
		}

		$jobData = array_key_exists('job_data', $overrides) && $overrides['job_data'] !== null
			? $overrides['job_data']
			: $row->job_data;

		// Merge instead of replace so an override that only sets `priority`
		// preserves the row's `group` (and vice versa). The override values
		// win on key collision.
		$config = $row->job_config;
		if (isset($overrides['job_config'])) {
			$config = array_merge($config, $overrides['job_config']);
		}
		$config['reference'] = $row->job_reference;

		return $this->getConnection()->transactional(function () use ($row, $jobData, $config, $overrides): bool {
			/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
			$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
			if (!$row->allow_concurrent && $queuedJobsTable->isQueued($row->job_reference, $row->job_task)) {
				return false;
			}

			$queuedJob = $queuedJobsTable->createJob($row->job_task, $jobData, $config);

			// Audit trail — info-level so it lands in the host app's
			// standard log channel. Trim the override payload to a sane
			// length to keep log files manageable; full data lives on the
			// queued_jobs row itself for the lifetime of the queue's
			// cleanup window.
			Log::write('info', sprintf(
				'[QueueScheduler.Override] Row #%d (%s) dispatched ad-hoc as queued_jobs #%d%s. Payload: %s',
				(int)$row->id,
				(string)$row->name,
				(int)$queuedJob->id,
				isset($overrides['triggered_by']) && $overrides['triggered_by'] !== ''
					? ' by ' . substr((string)$overrides['triggered_by'], 0, 128)
					: '',
				substr(json_encode([
					'job_data' => $jobData,
					'job_config' => $config,
				]) ?: '[]', 0, 4096),
			));

			// Deliberately do NOT touch last_run / next_run. The override
			// is an extra dispatch on top of the scheduled cadence; the
			// cron-fired rail continues to tick at its normal time.
			return true;
		});
	}

	/**
	 * @param mixed $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateUniqueName(mixed $value, array $context): bool {
		if (!is_string($value) || !$value) {
			return false;
		}

		$query = $this->find()
			->where(['name' => $value]);

		if (!empty($context['data']['id'])) {
			$query->where(['id !=' => $context['data']['id']]);
		}

		return $query->count() === 0;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function validateWindowTime(mixed $value): bool {
		if ($value === null || $value === '') {
			return true;
		}
		if (is_object($value) && method_exists($value, 'format')) {
			return true;
		}
		if (!is_string($value)) {
			return false;
		}

		return preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function validateWindowDaysOfWeek(mixed $value): bool {
		if ($value === null || $value === '') {
			return true;
		}
		if (!is_string($value)) {
			return false;
		}

		$parts = array_map('trim', explode(',', $value));
		$seen = [];
		foreach ($parts as $part) {
			if ($part === '' || !ctype_digit($part)) {
				return false;
			}
			$day = (int)$part;
			if ($day < 0 || $day > 6) {
				return false;
			}
			$seen[$day] = true;
		}

		return count($seen) > 0;
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	protected function validateFrequencyAsStringInterval(string $value): bool {
		try {
			// PHP 8.2 returns false on parse failure; 8.3+ throws. The explicit throw
			// below normalizes the false return into the same exception path so the
			// catch below is reachable on both versions (otherwise phpstan on 8.2
			// flags it as a dead catch since the function has no documented throws there).
			/** @var \DateInterval|false $interval */
			$interval = @DateInterval::createFromDateString($value);
			if (!$interval instanceof DateInterval) {
				throw new InvalidArgumentException('Invalid interval string: ' . $value);
			}
		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	protected function validateFrequencyAsDateInterval(string $value): bool {
		try {
			new DateInterval($value);
		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	protected function validateFrequencyAsCronExpression(string $value): bool {
		try {
			new CronExpression(SchedulerRow::normalizeCronExpression($value));
		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $value
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateCakeCommand(string $value, array $data): bool {
		preg_match('/\w+Command$/', $value, $matches);
		if (!$matches) {
			return false;
		}

		if (!class_exists($value)) {
			return false;
		}

		// Defense-in-depth: refuse to persist a class that the runtime
		// `CommandExecuteTask::run()` would later reject. Catches typos and
		// shrinks the post-class-loading attack surface (constructor side
		// effects on `new $class()`).
		return is_a($value, CommandInterface::class, true);
	}

	/**
	 * @param string $value
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateQueueTask(string $value, array $data): bool {
		preg_match('/\w+Task$/', $value, $matches);
		if (!$matches) {
			return false;
		}

		if (!class_exists($value)) {
			return false;
		}

		// Defense-in-depth: a `*Task` class that does not extend the queue
		// plugin's base Task is not a valid dispatch target — refuse to save.
		return is_a($value, Task::class, true);
	}

	/**
	 * Shell-command authoring is double-gated to match the dispatch-side gate in
	 * `findActive()`: rows of this type can only be persisted when raw execution
	 * is explicitly enabled. Without this, an admin could write a row that the
	 * runner would silently skip, or — if `allowRaw` is later flipped on — a
	 * pre-staged shell row would suddenly become live without a fresh review.
	 *
	 * @param string $value
	 * @param array $data
	 *
	 * @return string|bool
	 */
	protected function validateShellCommand(string $value, array $data): string|bool {
		if (!Configure::read('debug') && !Configure::read('QueueScheduler.allowRaw')) {
			return __d('queue_scheduler', 'Shell Command rows require QueueScheduler.allowRaw=true (or debug mode).');
		}

		return true;
	}

	/**
	 * @param string $value
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateCakeCommandParam(string $value, array $data): bool {
		if (!str_starts_with($value, '[')) {
			return false;
		}
		if (!str_ends_with($value, ']')) {
			return false;
		}

		$array = json_decode($value, true);

		return is_array($array) && !empty($array);
	}

	/**
	 * @param string $value
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateQueueTaskParam(string $value, array $data): bool {
		if (!str_starts_with($value, '{')) {
			return false;
		}
		if (!str_ends_with($value, '}')) {
			return false;
		}

		$array = json_decode($value, true);

		return is_array($array) && !empty($array);
	}

}
