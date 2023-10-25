<?php
declare(strict_types=1);

namespace QueueScheduler\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * QueueSchedulerRowsFixture
 */
class SchedulerRowsFixture extends TestFixture {

	/**
	 * @var string
	 */
	public $table = 'queue_scheduler_rows';

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
