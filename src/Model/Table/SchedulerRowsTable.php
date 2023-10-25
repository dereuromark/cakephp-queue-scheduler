<?php
declare(strict_types=1);

namespace QueueScheduler\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use QueueScheduler\Model\Entity\SchedulerRow;
use RuntimeException;

/**
 * Rows Model
 *
 * @method \QueueScheduler\Model\Entity\SchedulerRow newEmptyEntity()
 * @method \QueueScheduler\Model\Entity\SchedulerRow newEntity(array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\SchedulerRow> newEntities(array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow get($primaryKey, $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\SchedulerRow> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \QueueScheduler\Model\Entity\SchedulerRow saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow>|false saveMany(iterable $entities, $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow> saveManyOrFail(iterable $entities, $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow>|false deleteMany(iterable $entities, $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow> deleteManyOrFail(iterable $entities, $options = [])
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
			->notEmptyString('content');

		$validator
			->scalar('frequency')
			->maxLength('frequency', 140)
			->requirePresence('frequency', 'create')
			->notEmptyString('frequency');

		$validator
			->dateTime('last_run')
			->allowEmptyDateTime('last_run');

		$validator
			->boolean('allow_concurrent')
			->notEmptyString('allow_concurrent');

		return $validator;
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
	 * @param \Cake\ORM\Query $query
	 *
	 * @return \Cake\ORM\Query
	 */
	public function findActive(Query $query) {
		$conditions = ['enabled' => true];
		$debug = Configure::read('debug');
		if (!$debug && !Configure::read('QueueScheduler.allowRaw')) {
			$conditions['type !='] = SchedulerRow::TYPE_SHELL_COMMAND;
		}

		return $query->where($conditions);
	}

	/**
	 * @param \Cake\ORM\Query $query
	 *
	 * @return \Cake\ORM\Query
	 */
	public function findScheduled(Query $query) {
		return $query->where(['OR' => ['next_run IS' => null, 'next_run <=' => new FrozenTime()]]);
	}

	/**
	 * @param \QueueScheduler\Model\Entity\SchedulerRow $row
	 *
	 * @return bool
	 */
    public function run(SchedulerRow $row): bool
    {
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
		$row->last_run = new FrozenTime();
		$this->saveOrFail($row);

		return true;
    }

}
