<?php

namespace QueueScheduler\Queue\Task;

use Cake\Console\ConsoleIo;
use Queue\Queue\Task;

class CommandExecuteTask extends Task {

	/**
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		/** @var class-string<\Cake\Console\CommandInterface> $class */
		$class = $data['class'];
		$args = $data['args'] ?? [];
		$io = $data['io'] ?? new ConsoleIo();

		$command = new $class();
		$command->run($args, $io);
	}

}
