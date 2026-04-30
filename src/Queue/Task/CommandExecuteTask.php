<?php declare(strict_types=1);

namespace QueueScheduler\Queue\Task;

use Cake\Console\BaseCommand;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Queue\Model\QueueException;
use Queue\Queue\Task;

class CommandExecuteTask extends Task {

	/**
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @throws \Queue\Model\QueueException When the command class is missing, unknown, or not a CommandInterface.
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$class = $data['class'] ?? null;
		if (!is_string($class) || !class_exists($class) || !is_a($class, CommandInterface::class, true)) {
			throw new QueueException('Invalid command class: ' . var_export($class, true));
		}

		/** @var class-string<\Cake\Console\CommandInterface> $class */
		$args = $data['args'] ?? [];

		$stdout = tmpfile();
		$stderr = tmpfile();
		$io = new ConsoleIo(
			new ConsoleOutput($stdout),
			new ConsoleOutput($stderr),
		);

		$command = new $class();
		// Match what `Cake\Console\CommandRunner` does before running a
		// command: prefix `defaultName()` with the root ("cake") so the
		// name has the space `BaseCommand::getOptionParser()` expects
		// when it does `[$root, $name] = explode(' ', $this->name, 2)`.
		// Without this, any command whose getOptionParser() runs throws
		// `TypeError: ConsoleOptionParser::__construct(): Argument #1
		// ($command) must be of type string, null given`.
		// The str_contains guard preserves names that already include a
		// root (default `cake unknown`, custom roots like `app foo`), so
		// only single-token overrides like `'clear_sessions'` get fixed.
		if ($command instanceof BaseCommand && !str_contains($command->getName(), ' ')) {
			$command->setName('cake ' . $command::defaultName());
		}
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
