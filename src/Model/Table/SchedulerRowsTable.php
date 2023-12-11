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
use QueueScheduler\Model\Entity\SchedulerRow;
use RuntimeException;

/**
 * Rows Model
 *
 * @method \QueueScheduler\Model\Entity\SchedulerRow newEmptyEntity()
 * @method \QueueScheduler\Model\Entity\SchedulerRow newEntity(array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\SchedulerRow> newEntities(array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \QueueScheduler\Model\Entity\SchedulerRow findOrCreate($search, ?callable $callback = null, array $options = [])
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
	public $scaffoldSkipFields = ['last_run'];

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
			->notEmptyString('name');

		$validator
			->notEmptyString('type');

		$validator
			->scalar('content')
			->requirePresence('content', 'create')
			->notEmptyString('content')
			->add('content', 'validateContent', [
				'role' => 'validateContent',
				'provider' => 'table',
			]);

		$validator
			->scalar('frequency')
			->maxLength('frequency', 140)
			->requirePresence('frequency', 'create')
			->notEmptyString('frequency')
			->add('frequency', 'validateFrequency', [
				'role' => 'validateFrequency',
				'provider' => 'table',
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
	public function validateContent($value, array $context): bool {
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
	 * @return bool
	 */
	public function validateFrequency($value, array $context): bool {
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
		if (!isset($data['type']) || strlen((string)$data['type']) === 0 || (int)$data['type'] !== SchedulerRow::TYPE_QUEUE_TASK) {
			return;
		}

		if (empty($data['content'])) {
			return;
		}

		if (strpos($data['content'], '\\') !== false) {
			return;
		}

		$className = App::className($data['content'], 'Queue/Task', 'Task');
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
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
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

		$queuedJobsTable->createJob($row->job_task, $row->job_data, $config);
		$row->last_run = new DateTime();
		$this->saveOrFail($row);

		return true;
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	protected function validateFrequencyAsStringInterval(string $value): bool {
		try {
			$result = DateInterval::createFromDateString($value);

		} catch (Exception $e) {
			return false;
		}

		return $result !== false;
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
			new CronExpression($value);
		} catch (\Exception $e) {
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

}
