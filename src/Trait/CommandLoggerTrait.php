<?php declare(strict_types=1);

namespace QueueScheduler\Trait;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use QueueScheduler\Console\LoggingConsoleIo;
use ReflectionClass;

/**
 * CommandLoggerTrait provides logging functionality for CLI commands
 * Captures stdout/stderr and stores logs in files or database
 */
trait CommandLoggerTrait {

	use LocatorAwareTrait;

	/**
	 * @var \QueueScheduler\Console\LoggingConsoleIo|null
	 */
	protected ?LoggingConsoleIo $loggingIo = null;

	/**
	 * @var array<string, mixed> Logging configuration
	 */
	protected array $loggingConfig = [];

	/**
	 * Initialize logging for the command
	 *
	 * @param \Cake\Console\ConsoleIo $io The console IO
	 * @param array<string, mixed> $config Optional configuration
	 *
	 * @return \QueueScheduler\Console\LoggingConsoleIo
	 */
	protected function initializeLogging(ConsoleIo $io, array $config = []): LoggingConsoleIo {
		// If already a LoggingConsoleIo, return it
		if ($io instanceof LoggingConsoleIo) {
			$this->loggingIo = $io;
			$this->loggingConfig = $config;

			return $io;
		}

		// Use reflection to access protected properties from ConsoleIo
		$reflection = new ReflectionClass($io);

		$outProperty = $reflection->getProperty('_out');
		$outProperty->setAccessible(true);
		$out = $outProperty->getValue($io);

		$errProperty = $reflection->getProperty('_err');
		$errProperty->setAccessible(true);
		$err = $errProperty->getValue($io);

		$inProperty = $reflection->getProperty('_in');
		$inProperty->setAccessible(true);
		$in = $inProperty->getValue($io);

		$helpersProperty = $reflection->getProperty('_helpers');
		$helpersProperty->setAccessible(true);
		$helpers = $helpersProperty->getValue($io);

		// Create new LoggingConsoleIo wrapper
		$this->loggingIo = new LoggingConsoleIo($out, $err, $in, $helpers);

		// Apply configuration
		$this->loggingConfig = array_merge($this->getDefaultLoggingConfig(), $config);

		if (!$this->loggingConfig['enabled']) {
			$this->loggingIo->setLoggingEnabled(false);
		}

		return $this->loggingIo;
	}

	/**
	 * Get default logging configuration
	 *
	 * @return array<string, mixed>
	 */
	protected function getDefaultLoggingConfig(): array {
		$defaults = [
			'enabled' => true,
			'store_in_database' => true,
			'store_in_file' => true,
			'file_path' => LOGS . 'scheduler' . DS,
			'file_rotate' => true,
			'file_max_size' => '10MB',
			'file_max_files' => 10,
			'include_timestamp' => true,
			'include_command_args' => true,
		];

		// Merge with configuration from app
		$appConfig = Configure::read('QueueScheduler.logging', []);

		return array_merge($defaults, $appConfig);
	}

	/**
	 * Save the captured logs
	 *
	 * @param string $commandName The name of the command
	 * @param \Cake\Console\Arguments|null $args The command arguments
	 * @param array<string, mixed> $metadata Additional metadata
	 *
	 * @return void
	 */
	protected function saveCommandLogs(string $commandName, ?Arguments $args = null, array $metadata = []): void {
		if (!$this->loggingIo || !$this->loggingConfig['enabled']) {
			return;
		}

		$buffers = $this->loggingIo->getBuffers();

		// Prepare log data
		$logData = [
			'command' => $commandName,
			'stdout' => $buffers['stdout'],
			'stderr' => $buffers['stderr'],
			'executed_at' => new DateTime(),
			'metadata' => $metadata,
		];

		if ($this->loggingConfig['include_command_args'] && $args) {
			$logData['arguments'] = $this->serializeArguments($args);
		}

		// Store in database if enabled
		if ($this->loggingConfig['store_in_database']) {
			$this->saveToDatabase($logData);
		}

		// Store in file if enabled
		if ($this->loggingConfig['store_in_file']) {
			$this->saveToFile($logData);
		}

		// Clear buffers after saving
		if ($this->loggingIo) {
			$this->loggingIo->clearBuffers();
		}
	}

	/**
	 * Save log data to database
	 *
	 * @param array<string, mixed> $logData The log data
	 *
	 * @return void
	 */
	protected function saveToDatabase(array $logData): void {
		try {
			$table = $this->fetchTable('QueueScheduler.CommandLogs');

			$entity = $table->newEntity($logData);
			$table->save($entity);
		} catch (\Exception $e) {
			// Fallback to file logging if database save fails
			Log::error('Failed to save command log to database: ' . $e->getMessage());
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
		$filePath = $this->loggingConfig['file_path'];

		// Create directory if it doesn't exist
		if (!is_dir($filePath)) {
			mkdir($filePath, 0777, true);
		}

		// Generate filename
		$filename = $filePath . 'command_' . date('Y-m-d') . '.log';

		// Format log entry
		$logEntry = $this->formatLogEntry($logData);

		// Check file rotation
		if ($this->loggingConfig['file_rotate']) {
			$this->rotateLogFile($filename);
		}

		// Append to file
		file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
	}

	/**
	 * Format log entry for file storage
	 *
	 * @param array<string, mixed> $logData The log data
	 *
	 * @return string
	 */
	protected function formatLogEntry(array $logData): string {
		$separator = str_repeat('=', 80) . PHP_EOL;
		$entry = $separator;

		if ($this->loggingConfig['include_timestamp']) {
			$entry .= '[' . $logData['executed_at']->format('Y-m-d H:i:s') . '] ';
		}

		$entry .= 'Command: ' . $logData['command'] . PHP_EOL;

		if (!empty($logData['arguments'])) {
			$entry .= 'Arguments: ' . json_encode($logData['arguments']) . PHP_EOL;
		}

		if (!empty($logData['metadata'])) {
			$entry .= 'Metadata: ' . json_encode($logData['metadata']) . PHP_EOL;
		}

		$entry .= PHP_EOL . '--- STDOUT ---' . PHP_EOL;
		$entry .= $logData['stdout'] ?: '(empty)';
		$entry .= PHP_EOL . PHP_EOL . '--- STDERR ---' . PHP_EOL;
		$entry .= $logData['stderr'] ?: '(empty)';
		$entry .= PHP_EOL . $separator . PHP_EOL;

		return $entry;
	}

	/**
	 * Rotate log file if it exceeds max size
	 *
	 * @param string $filename The log file path
	 *
	 * @return void
	 */
	protected function rotateLogFile(string $filename): void {
		if (!file_exists($filename)) {
			return;
		}

		$maxSize = $this->parseSize($this->loggingConfig['file_max_size']);
		$currentSize = filesize($filename);

		if ($currentSize < $maxSize) {
			return;
		}

		// Rotate files
		$maxFiles = (int)$this->loggingConfig['file_max_files'];
		for ($i = $maxFiles - 1; $i > 0; $i--) {
			$oldFile = $filename . '.' . $i;
			$newFile = $filename . '.' . ($i + 1);

			if (file_exists($oldFile)) {
				if ($i === $maxFiles - 1) {
					unlink($oldFile);
				} else {
					rename($oldFile, $newFile);
				}
			}
		}

		// Rotate current file
		rename($filename, $filename . '.1');
	}

	/**
	 * Parse size string to bytes
	 *
	 * @param string $size Size string (e.g., '10MB', '1GB')
	 *
	 * @return int
	 */
	protected function parseSize(string $size): int {
		$units = ['B' => 1, 'KB' => 1024, 'MB' => 1048576, 'GB' => 1073741824];
		$size = strtoupper(trim($size));

		foreach ($units as $unit => $multiplier) {
			if (str_ends_with($size, $unit)) {
				return (int)((float)str_replace($unit, '', $size) * $multiplier);
			}
		}

		return (int)$size;
	}

	/**
	 * Serialize command arguments for storage
	 *
	 * @param \Cake\Console\Arguments $args The arguments
	 *
	 * @return array<string, mixed>
	 */
	protected function serializeArguments(Arguments $args): array {
		return [
			'arguments' => $args->getArguments(),
			'options' => $args->getOptions(),
		];
	}

	/**
	 * Get the logging IO instance
	 *
	 * @return \QueueScheduler\Console\LoggingConsoleIo|null
	 */
	public function getLoggingIo(): ?LoggingConsoleIo {
		return $this->loggingIo;
	}

}
