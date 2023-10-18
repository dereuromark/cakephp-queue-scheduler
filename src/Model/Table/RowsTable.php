<?php
declare(strict_types=1);

namespace QueueScheduler\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Rows Model
 *
 * @method \QueueScheduler\Model\Entity\Row newEmptyEntity()
 * @method \QueueScheduler\Model\Entity\Row newEntity(array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\Row> newEntities(array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\Row get($primaryKey, $options = [])
 * @method \QueueScheduler\Model\Entity\Row findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \QueueScheduler\Model\Entity\Row patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\Row> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\Row|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \QueueScheduler\Model\Entity\Row saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method array<\QueueScheduler\Model\Entity\Row>|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method array<\QueueScheduler\Model\Entity\Row>|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method array<\QueueScheduler\Model\Entity\Row>|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method array<\QueueScheduler\Model\Entity\Row>|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RowsTable extends Table {

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

}
