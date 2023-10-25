<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * Demo seed.
 */
class DemoSeed extends AbstractSeed {

	/**
	 * Run Method.
	 *
	 * Write your database seeder using this method.
	 *
	 * More information on writing seeds is available here:
	 * https://book.cakephp.org/phinx/0/en/seeding.html
	 *
	 * @return void
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'name' => 'Example Shell snippet',
				'type' => \QueueScheduler\Model\Entity\Row::TYPE_SHELL_COMMAND,
				'frequency' => '+10seconds',
				'content' => 'uname',
				'enabled' => true,
			],
			[
				'id' => 2,
				'name' => 'Example Cake Command',
				'type' => \QueueScheduler\Model\Entity\Row::TYPE_CAKE_COMMAND,
				'frequency' => '+1minute',
				'content' => \Cake\Command\SchemacacheBuildCommand::class,
				'enabled' => true,
			],
			[
				'id' => 3,
				'name' => 'Example Queue Task',
				'type' => \QueueScheduler\Model\Entity\Row::TYPE_QUEUE_TASK,
				'frequency' => '+30seconds',
				'content' => \Queue\Queue\Task\ExampleTask::class,
				'enabled' => true,
			],
		];

		$table = $this->table(\Cake\ORM\TableRegistry::getTableLocator()->get('QueueScheduler.Rows')->getTable());
		$table->insert($data)->save();
	}

}
