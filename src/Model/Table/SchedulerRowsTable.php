<?php declare(strict_types=1);

namespace QueueScheduler\Model\Table;

use ArrayObject;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cron\CronExpression;
use DateInterval;
use Exception;
use InvalidArgumentException;
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

		// Stored as JSON text but exposed as array in PHP — Cake handles the
		// encode/decode round-trip transparently.
		$this->getSchema()->setColumnType('job_config', 'json');
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
				'message' => __('This name is already in use.'),
			]);

		$validator
			->notEmptyString('type');

		$validator
			->scalar('content')
			->requirePresence('content', 'create')
			->notEmptyString('content')
			->add('content', 'validateContent', [
				'provider' => 'table',
				'message' => __('Content does not match the chosen type. Use a Task class (or "Plugin.Task" alias) for Queue tasks, a Command class (or "Plugin.Command" alias) for Cake commands, or a shell command string.'),
			]);

		$validator
			->scalar('param')
			->allowEmptyString('param')
			->add('param', 'validateParam', [
				'provider' => 'table',
				'message' => __('Param must be a JSON object {…} for Queue tasks or a JSON array […] for Cake commands. Shell commands cannot have a param.'),
			]);

		$validator
			->scalar('job_config')
			->allowEmptyString('job_config')
			->add('job_config', 'validateJobConfig', [
				'provider' => 'table',
				'message' => __('Job Config must be a JSON object with allowed keys only: priority (int 1-10) and group (string).'),
			]);

		$validator
			->scalar('frequency')
			->maxLength('frequency', 140)
			->requirePresence('frequency', 'create')
			->notEmptyString('frequency')
			->add('frequency', 'validateFrequency', [
				'provider' => 'table',
				'message' => __('Must be a cron expression ("0 11 * * *"), an @-shortcut ("@daily", "@minutely"), a relative interval ("+30 seconds"), or an ISO 8601 duration ("P2D").'),
			]);

		$validator
			->dateTime('last_run')
			->allowEmptyDateTime('last_run');

		$validator
			->boolean('allow_concurrent')
			->notEmptyString('allow_concurrent');

		return $validator;
	}

	/**
	 * @param mixed $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateContent(mixed $value, array $context): bool {
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
				return $value === '' ? true : __('Cannot have separate param data for shell command.');
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
		if ($entity->next_run === null || $entity->isDirty('frequency') || $entity->isDirty('last_run')) {
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

		$config = $row->job_config;
		$config['reference'] = $row->job_reference;

		/** @var \Queue\Model\Table\QueuedJobsTable $queuedJobsTable */
		$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		if (!$row->allow_concurrent && $queuedJobsTable->isQueued($row->job_reference, $row->job_task)) {
			return false;
		}

		$queuedJob = $queuedJobsTable->createJob($row->job_task, $row->job_data, $config);
		$row->last_run = new DateTime();
		$row->last_queued_job_id = $queuedJob->id;
		$this->saveOrFail($row);

		return true;
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

		return class_exists($value);
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

		return class_exists($value);
	}

	/**
	 * @param string $value
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function validateShellCommand(string $value, array $data): bool {
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
