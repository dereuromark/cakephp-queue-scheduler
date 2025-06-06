<?php declare(strict_types=1);

use Migrations\BaseMigration;

class QueueSchedulerParam extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$this->table('queue_scheduler_rows')
			->addColumn('param', 'text', [
				'default' => null,
				'null' => true,
				'after' => 'content',
			])
			->update();
	}

}
