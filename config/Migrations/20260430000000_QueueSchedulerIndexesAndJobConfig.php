<?php declare(strict_types=1);

use Migrations\BaseMigration;

class QueueSchedulerIndexesAndJobConfig extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$this->table('queue_scheduler_rows')
			->addColumn('job_config', 'text', [
				'default' => null,
				'null' => true,
				'comment' => 'JSON-encoded queue config (priority, queue, group, notBefore, ...).',
			])
			->addIndex(['enabled', 'next_run'], ['name' => 'enabled_next_run'])
			->addIndex(['name'], ['name' => 'unique_name', 'unique' => true])
			->update();
	}

}
