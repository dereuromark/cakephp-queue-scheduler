<?php declare(strict_types=1);

namespace QueueScheduler\Console;

use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\HelperRegistry;

/**
 * LoggingConsoleIo extends ConsoleIo to capture all output (stdout/stderr)
 * for logging purposes while maintaining normal console behavior.
 */
class LoggingConsoleIo extends ConsoleIo {

	/**
	 * @var array<string> Buffer for stdout messages
	 */
	protected array $stdoutBuffer = [];

	/**
	 * @var array<string> Buffer for stderr messages
	 */
	protected array $stderrBuffer = [];

	/**
	 * @var bool Whether logging is enabled
	 */
	protected bool $loggingEnabled = true;

	/**
	 * Constructor
	 *
	 * @param \Cake\Console\ConsoleOutput|null $out The output stream
	 * @param \Cake\Console\ConsoleOutput|null $err The error stream
	 * @param \Cake\Console\ConsoleInput|null $in The input stream
	 * @param \Cake\Console\HelperRegistry|null $helpers The helper registry
	 */
	public function __construct(
		?ConsoleOutput $out = null,
		?ConsoleOutput $err = null,
		?ConsoleInput $in = null,
		?HelperRegistry $helpers = null,
	) {
		parent::__construct($out, $err, $in, $helpers);
	}

	/**
	 * @inheritDoc
	 */
	public function out(string|array $message = '', int $newlines = 1, int $level = ConsoleIo::NORMAL): ?int {
		if ($this->loggingEnabled) {
			$this->captureOutput($message, 'stdout', $newlines);
		}

		return parent::out($message, $newlines, $level);
	}

	/**
	 * @inheritDoc
	 */
	public function info(string|array $message, int $newlines = 1, int $level = ConsoleIo::NORMAL): ?int {
		if ($this->loggingEnabled) {
			$this->captureOutput('[INFO] ' . $this->formatMessage($message), 'stdout', $newlines);
		}

		return parent::info($message, $newlines, $level);
	}

	/**
	 * @inheritDoc
	 */
	public function success(string|array $message, int $newlines = 1, int $level = ConsoleIo::NORMAL): ?int {
		if ($this->loggingEnabled) {
			$this->captureOutput('[SUCCESS] ' . $this->formatMessage($message), 'stdout', $newlines);
		}

		return parent::success($message, $newlines, $level);
	}

	/**
	 * @inheritDoc
	 */
	public function warning(string|array $message, int $newlines = 1): int {
		if ($this->loggingEnabled) {
			$this->captureOutput('[WARNING] ' . $this->formatMessage($message), 'stderr', $newlines);
		}

		return parent::warning($message, $newlines);
	}

	/**
	 * @inheritDoc
	 */
	public function err(string|array $message = '', int $newlines = 1): int {
		if ($this->loggingEnabled) {
			$this->captureOutput($message, 'stderr', $newlines);
		}

		return parent::err($message, $newlines);
	}

	/**
	 * @inheritDoc
	 */
	public function error(string|array $message, int $newlines = 1): int {
		if ($this->loggingEnabled) {
			$this->captureOutput('[ERROR] ' . $this->formatMessage($message), 'stderr', $newlines);
		}

		return parent::error($message, $newlines);
	}

	/**
	 * Capture output to the appropriate buffer
	 *
	 * @param array|string $message The message to capture
	 * @param string $stream The stream type ('stdout' or 'stderr')
	 * @param int $newlines Number of newlines to append
	 *
	 * @return void
	 */
	protected function captureOutput(string|array $message, string $stream, int $newlines): void {
		$formattedMessage = $this->formatMessage($message);
		$formattedMessage .= str_repeat(PHP_EOL, $newlines);

		if ($stream === 'stdout') {
			$this->stdoutBuffer[] = $formattedMessage;
		} else {
			$this->stderrBuffer[] = $formattedMessage;
		}
	}

	/**
	 * Format message for logging
	 *
	 * @param array|string $message The message to format
	 *
	 * @return string
	 */
	protected function formatMessage(string|array $message): string {
		if (is_array($message)) {
			return implode(PHP_EOL, $message);
		}

		return $message;
	}

	/**
	 * Get the stdout buffer
	 *
	 * @return string
	 */
	public function getStdoutBuffer(): string {
		return implode('', $this->stdoutBuffer);
	}

	/**
	 * Get the stderr buffer
	 *
	 * @return string
	 */
	public function getStderrBuffer(): string {
		return implode('', $this->stderrBuffer);
	}

	/**
	 * Get both buffers as an array
	 *
	 * @return array{stdout: string, stderr: string}
	 */
	public function getBuffers(): array {
		return [
			'stdout' => $this->getStdoutBuffer(),
			'stderr' => $this->getStderrBuffer(),
		];
	}

	/**
	 * Clear all buffers
	 *
	 * @return void
	 */
	public function clearBuffers(): void {
		$this->stdoutBuffer = [];
		$this->stderrBuffer = [];
	}

	/**
	 * Enable or disable logging
	 *
	 * @param bool $enabled Whether to enable logging
	 *
	 * @return void
	 */
	public function setLoggingEnabled(bool $enabled): void {
		$this->loggingEnabled = $enabled;
	}

	/**
	 * Check if logging is enabled
	 *
	 * @return bool
	 */
	public function isLoggingEnabled(): bool {
		return $this->loggingEnabled;
	}

}
