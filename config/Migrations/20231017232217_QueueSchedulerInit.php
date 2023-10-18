<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class QueueSchedulerInit extends AbstractMigration {

	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$this->table('queue_scheduler_rows')
			->addColumn('name', 'string', [
				'default' => null,
				'limit' => 140,
				'null' => false,
			])
			->addColumn('type', 'tinyinteger', [
				'default' => 0,
				'limit' => 2,
				'null' => false,
			])
			->addColumn('content', 'text', [
				'default' => null,
				'null' => false,
			])
			->addColumn('frequency', 'string', [
				'default' => null,
				'limit' => 140,
				'null' => false,
			])
			->addColumn('last_run', 'datetime', [
				'default' => null,
				'null' => true,
			])
			->addColumn('allow_concurrent', 'boolean', [
				'default' => false,
				'null' => false,
			])
			->addColumn('enabled', 'boolean', [
				'default' => false,
				'null' => false,
			])
			->addColumn('created', 'datetime', [
				'default' => null,
				'null' => true,
			])
			->addColumn('modified', 'datetime', [
				'default' => null,
				'null' => true,
			])
			->create();
	}

}
