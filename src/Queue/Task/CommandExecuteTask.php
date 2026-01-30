<?php declare(strict_types=1);

namespace QueueScheduler\Queue\Task;

use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Queue\Model\QueueException;
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

		$stdout = tmpfile();
		$stderr = tmpfile();
		$io = new ConsoleIo(
			new ConsoleOutput($stdout),
			new ConsoleOutput($stderr),
		);

		$command = new $class();
		$exitCode = $command->run($args, $io);

		// Forward captured output to $this->io for Processor capture
		rewind($stdout);
		$output = stream_get_contents($stdout);
		fclose($stdout);
		if ($output) {
			$this->io->out(rtrim($output));
		}

		rewind($stderr);
		$errOutput = stream_get_contents($stderr);
		fclose($stderr);
		if ($errOutput) {
			$this->io->error(rtrim($errOutput));
		}

		if ($exitCode !== null && $exitCode !== CommandInterface::CODE_SUCCESS) {
			throw new QueueException('Command ' . $class . ' exited with code ' . $exitCode);
		}
	}

}
