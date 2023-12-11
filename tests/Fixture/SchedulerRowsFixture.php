<?php declare(strict_types=1);

namespace QueueScheduler\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SchedulerRowsFixture extends TestFixture {

	/**
	 * @var string
	 */
	public string $table = 'queue_scheduler_rows';

	/**
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
		'name' => ['type' => 'string', 'length' => 140, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'type' => ['type' => 'tinyinteger', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
		'content' => ['type' => 'text', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'frequency' => ['type' => 'string', 'length' => 140, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'last_run' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
		'next_run' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
		'allow_concurrent' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
		'enabled' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
		'created' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
		],
		'_options' => [
			'engine' => 'InnoDB',
			'collation' => 'utf8mb4_unicode_ci',
		],
	];

	/**
	 * Init method
	 *
	 * @return void
	 */
	public function init(): void {
		$this->records = [
			[
				'name' => 'Lorem ipsum dolor sit amet',
				'type' => 1,
				'content' => 'umask',
				'frequency' => '+ 30 seconds',
				'last_run' => '2023-10-18 01:50:09',
				'allow_concurrent' => 0,
				'enabled' => 1,
				'created' => '2023-10-18 01:50:09',
				'modified' => '2023-10-18 01:50:09',
			],
		];
		parent::init();
	}

}
