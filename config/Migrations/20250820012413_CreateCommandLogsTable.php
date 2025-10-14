<?php declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateCommandLogsTable extends AbstractMigration {

	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('queue_scheduler_command_logs');

		$table->addColumn('command', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => false,
		]);

		$table->addColumn('arguments', 'text', [
			'default' => null,
			'null' => true,
		]);

		$table->addColumn('stdout', 'text', [
			'default' => null,
			'limit' => 16777215, // MEDIUMTEXT
			'null' => true,
		]);

		$table->addColumn('stderr', 'text', [
			'default' => null,
			'limit' => 16777215, // MEDIUMTEXT
			'null' => true,
		]);

		$table->addColumn('job_id', 'integer', [
			'default' => null,
			'null' => true,
			'signed' => false,
		]);

		$table->addColumn('execution_time', 'float', [
			'default' => null,
			'null' => true,
		]);

		$table->addColumn('success', 'boolean', [
			'default' => true,
			'null' => false,
		]);

		$table->addColumn('error_message', 'text', [
			'default' => null,
			'null' => true,
		]);

		$table->addColumn('metadata', 'text', [
			'default' => null,
			'null' => true,
			'comment' => 'JSON encoded metadata',
		]);

		$table->addColumn('executed_at', 'datetime', [
			'default' => null,
			'null' => false,
		]);

		$table->addColumn('created', 'datetime', [
			'default' => null,
			'null' => false,
		]);

		// Add indexes for better query performance
		$table->addIndex(['command']);
		$table->addIndex(['job_id']);
		$table->addIndex(['success']);
		$table->addIndex(['executed_at']);
		$table->addIndex(['created']);

		// Composite index for common queries
		$table->addIndex(['command', 'executed_at']);

		$table->create();
	}

	/**
	 * @return void
	 */
	public function down(): void {
		$this->table('queue_scheduler_command_logs')->drop()->save();
	}

}
