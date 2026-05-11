<?php declare(strict_types=1);

namespace QueueScheduler\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SchedulerRunsFixture extends TestFixture {

	/**
	 * @var string
	 */
	public string $table = 'queue_scheduler_runs';

	/**
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer', 'length' => null, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
		'scheduler_row_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => 'FK to queue_scheduler_rows.id'],
		'queued_job_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
		'status' => ['type' => 'string', 'length' => 16, 'null' => false, 'default' => 'queued', 'comment' => '', 'precision' => null],
		'dispatched_at' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
		'completed_at' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
		'duration_ms' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
		'failure_message' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
		'created' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
		],
		'_indexes' => [
			'row_dispatched' => ['type' => 'index', 'columns' => ['scheduler_row_id', 'dispatched_at'], 'length' => []],
			'queued_job_id' => ['type' => 'index', 'columns' => ['queued_job_id'], 'length' => []],
			'status' => ['type' => 'index', 'columns' => ['status'], 'length' => []],
		],
	];

	/**
	 * @var array
	 */
	public array $records = [];

}
