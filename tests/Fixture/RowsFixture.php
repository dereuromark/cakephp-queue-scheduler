<?php
declare(strict_types=1);

namespace QueueScheduler\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * QueueSchedulerRowsFixture
 */
class RowsFixture extends TestFixture {

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
				'id' => 1,
				'name' => 'Lorem ipsum dolor sit amet',
				'type' => 1,
				'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
				'frequency' => 'Lorem ipsum dolor sit amet',
				'last_run' => '2023-10-18 01:50:09',
				'allow_concurrent' => 1,
				'enabled' => 1,
				'created' => '2023-10-18 01:50:09',
				'modified' => '2023-10-18 01:50:09',
			],
		];
		parent::init();
	}

}
