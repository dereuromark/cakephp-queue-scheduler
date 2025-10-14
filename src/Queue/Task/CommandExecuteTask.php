<?php

namespace QueueScheduler\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Queue\Queue\Task;
use QueueScheduler\Console\LoggingConsoleIo;

class CommandExecuteTask extends Task {

	use LocatorAwareTrait;

	/**
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		/** @var class-string<\Cake\Console\CommandInterface> $class */
		$class = $data['class'];
		$args = $data['args'] ?? [];
		$enableLogging = $data['enable_logging'] ?? Configure::read('QueueScheduler.logging.enabled', true);

		// Create logging IO if enabled
		if ($enableLogging) {
			$io = new LoggingConsoleIo();
		} else {
			$io = $data['io'] ?? new ConsoleIo();
		}

		$startTime = microtime(true);

		try {
			$command = new $class();
			$exitCode = $command->run($args, $io);

			$success = true;
			$errorMessage = null;
		} catch (\Exception $e) {
			$exitCode = 1;
			$success = false;
			$errorMessage = $e->getMessage();
			if ($io instanceof LoggingConsoleIo) {
				$io->error('Command execution failed: ' . $errorMessage);
			}
		}

		$executionTime = round(microtime(true) - $startTime, 3);

		// Save logs if logging is enabled
		if ($enableLogging && $io instanceof LoggingConsoleIo) {
			$this->saveExecutionLog($class, $args, $io, $jobId, $executionTime, $success, $errorMessage);
		}
	}

	/**
	 * Save execution log to database/file
	 *
	 * @param string $commandClass The command class
	 * @param array $args The command arguments
	 * @param \QueueScheduler\Console\LoggingConsoleIo $io The logging IO
	 * @param int $jobId The job ID
	 * @param float $executionTime The execution time
	 * @param bool $success Whether the command succeeded
	 * @param string|null $errorMessage The error message if failed
	 *
	 * @return void
	 */
	protected function saveExecutionLog(
		string $commandClass,
		array $args,
		LoggingConsoleIo $io,
		int $jobId,
		float $executionTime,
		bool $success,
		?string $errorMessage,
	): void {
		$buffers = $io->getBuffers();

		$logData = [
			'command' => $commandClass,
			'arguments' => json_encode($args),
			'stdout' => $buffers['stdout'],
			'stderr' => $buffers['stderr'],
			'executed_at' => new DateTime(),
			'job_id' => $jobId,
			'execution_time' => $executionTime,
			'success' => $success,
			'error_message' => $errorMessage,
		];

		// Try to save to database
		if (Configure::read('QueueScheduler.logging.store_in_database', true)) {
			try {
				$table = $this->fetchTable('QueueScheduler.CommandLogs');
				$entity = $table->newEntity($logData);
				$table->save($entity);
			} catch (\Exception $e) {
				Log::error('Failed to save command execution log to database: ' . $e->getMessage());
				$this->saveToFile($logData);
			}
		}

		// Also save to file if configured
		if (Configure::read('QueueScheduler.logging.store_in_file', true)) {
			$this->saveToFile($logData);
		}
	}

	/**
	 * Save log data to file
	 *
	 * @param array<string, mixed> $logData The log data
	 *
	 * @return void
	 */
	protected function saveToFile(array $logData): void {
		$filePath = Configure::read('QueueScheduler.logging.file_path', LOGS . 'scheduler' . DS);

		// Create directory if it doesn't exist
		if (!is_dir($filePath)) {
			mkdir($filePath, 0777, true);
		}

		$filename = $filePath . 'command_execute_' . date('Y-m-d') . '.log';

		$separator = str_repeat('=', 80) . PHP_EOL;
		$entry = $separator;
		$entry .= '[' . $logData['executed_at']->format('Y-m-d H:i:s') . '] ';
		$entry .= 'Command: ' . $logData['command'] . PHP_EOL;
		$entry .= 'Job ID: ' . $logData['job_id'] . PHP_EOL;
		$entry .= 'Arguments: ' . $logData['arguments'] . PHP_EOL;
		$entry .= 'Execution Time: ' . $logData['execution_time'] . 's' . PHP_EOL;
		$entry .= 'Success: ' . ($logData['success'] ? 'Yes' : 'No') . PHP_EOL;

		if ($logData['error_message']) {
			$entry .= 'Error: ' . $logData['error_message'] . PHP_EOL;
		}

		$entry .= PHP_EOL . '--- STDOUT ---' . PHP_EOL;
		$entry .= $logData['stdout'] ?: '(empty)';
		$entry .= PHP_EOL . PHP_EOL . '--- STDERR ---' . PHP_EOL;
		$entry .= $logData['stderr'] ?: '(empty)';
		$entry .= PHP_EOL . $separator . PHP_EOL;

		file_put_contents($filename, $entry, FILE_APPEND | LOCK_EX);
	}

}
