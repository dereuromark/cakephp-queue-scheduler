<?php declare(strict_types=1);

use Cake\Command\SchemacacheBuildCommand;
use Cake\ORM\TableRegistry;
use Migrations\BaseSeed;
use Queue\Queue\Task\ExampleTask;
use QueueScheduler\Model\Entity\SchedulerRow;

/**
 * Demo seed.
 */
class DemoSeed extends BaseSeed {

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
				'name' => 'Example Shell snippet',
				'type' => SchedulerRow::TYPE_SHELL_COMMAND,
				'frequency' => '+10seconds',
				'content' => 'uname',
				'enabled' => true,
			],
			[
				'name' => 'Example Cake Command',
				'type' => SchedulerRow::TYPE_CAKE_COMMAND,
				'frequency' => '+1minute',
				'content' => SchemacacheBuildCommand::class,
				'enabled' => true,
			],
			[
				'name' => 'Example Queue Task',
				'type' => SchedulerRow::TYPE_QUEUE_TASK,
				'frequency' => '+30seconds',
				'content' => ExampleTask::class,
				'enabled' => true,
			],
		];

		$table = $this->table(TableRegistry::getTableLocator()->get('QueueScheduler.SchedulerRows')->getTable());
		$table->insert($data)->save();
	}

}
