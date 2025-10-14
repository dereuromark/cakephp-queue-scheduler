<?php declare(strict_types=1);

namespace QueueScheduler\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CommandLogs Model
 *
 * @method \QueueScheduler\Model\Entity\CommandLog newEmptyEntity()
 * @method \QueueScheduler\Model\Entity\CommandLog newEntity(array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\CommandLog> newEntities(array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\CommandLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \QueueScheduler\Model\Entity\CommandLog findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \QueueScheduler\Model\Entity\CommandLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\QueueScheduler\Model\Entity\CommandLog> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \QueueScheduler\Model\Entity\CommandLog|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \QueueScheduler\Model\Entity\CommandLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CommandLogsTable extends Table {

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('queue_scheduler_command_logs');
		$this->setDisplayField('id');
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
			->scalar('command')
			->maxLength('command', 255)
			->requirePresence('command', 'create')
			->notEmptyString('command');

		$validator
			->scalar('arguments')
			->allowEmptyString('arguments');

		$validator
			->scalar('stdout')
			->allowEmptyString('stdout');

		$validator
			->scalar('stderr')
			->allowEmptyString('stderr');

		$validator
			->integer('job_id')
			->allowEmptyString('job_id');

		$validator
			->numeric('execution_time')
			->allowEmptyString('execution_time');

		$validator
			->boolean('success')
			->notEmptyString('success');

		$validator
			->scalar('error_message')
			->allowEmptyString('error_message');

		$validator
			->scalar('metadata')
			->allowEmptyString('metadata');

		$validator
			->dateTime('executed_at')
			->requirePresence('executed_at', 'create')
			->notEmptyDateTime('executed_at');

		return $validator;
	}

	/**
	 * Find logs for a specific command
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query The query
	 * @param array $options Options containing 'command' key
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findByCommand(SelectQuery $query, array $options): SelectQuery {
		return $query->where(['command' => $options['command']]);
	}

	/**
	 * Find failed logs
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query The query
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findFailed(SelectQuery $query): SelectQuery {
		return $query->where(['success' => false]);
	}

	/**
	 * Find successful logs
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query The query
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findSuccessful(SelectQuery $query): SelectQuery {
		return $query->where(['success' => true]);
	}

	/**
	 * Find logs with errors in stderr
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query The query
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findWithErrors(SelectQuery $query): SelectQuery {
		return $query->where(['stderr IS NOT' => null, 'stderr !=' => '']);
	}

	/**
	 * Find recent logs (last 24 hours by default)
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query The query
	 * @param array $options Options containing optional 'hours' key
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findRecent(SelectQuery $query, array $options = []): SelectQuery {
		$hours = $options['hours'] ?? 24;
		$since = new DateTime('-' . $hours . ' hours');

		return $query->where(['executed_at >=' => $since]);
	}

	/**
	 * Clean up old logs based on retention period
	 *
	 * @param int $retentionDays Number of days to retain logs
	 *
	 * @return int Number of deleted records
	 */
	public function cleanupOldLogs(int $retentionDays): int {
		$cutoffDate = new DateTime('-' . $retentionDays . ' days');

		return $this->deleteAll(['created <' => $cutoffDate]);
	}

	/**
	 * Get statistics for command execution
	 *
	 * @param string|null $command Optional command filter
	 * @param \Cake\I18n\DateTime|null $since Optional date filter
	 *
	 * @return array Statistics
	 */
	public function getStatistics(?string $command = null, ?DateTime $since = null): array {
		$query = $this->find();

		if ($command) {
			$query->where(['command' => $command]);
		}

		if ($since) {
			$query->where(['executed_at >=' => $since]);
		}

		$total = $query->count();

		$successful = (clone $query)->where(['success' => true])->count();
		$failed = (clone $query)->where(['success' => false])->count();

		$avgExecutionTime = (clone $query)
			->select(['avg_time' => $query->func()->avg('execution_time')])
			->first();

		return [
			'total' => $total,
			'successful' => $successful,
			'failed' => $failed,
			'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
			'avg_execution_time' => $avgExecutionTime ? round($avgExecutionTime->avg_time, 3) : 0,
		];
	}

}
