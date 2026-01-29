<?php declare(strict_types=1);

use Migrations\BaseMigration;

class QueueSchedulerJobLink extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$this->table('queue_scheduler_rows')
			->addColumn('last_queued_job_id', 'integer', [
				'default' => null,
				'null' => true,
				'signed' => false,
			])
			->addIndex(['last_queued_job_id'])
			->update();
	}

}
