<?php declare(strict_types=1);

namespace QueueScheduler\Command;

use Cake\Collection\Collection;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use QueueScheduler\Scheduler\Lock\FileLock;
use QueueScheduler\Scheduler\Lock\LockInterface;
use QueueScheduler\Scheduler\Scheduler;
use Throwable;

/**
 * Run command.
 */
class RunCommand extends Command {

	use LogTrait;

	/**
	 * Maximum seconds to wait for the lock before giving up. Tied to the
	 * cron interval; tests may override via subclassing.
	 *
	 * @var int
	 */
	protected int $lockAcquireTimeout = 30;

	/**
	 * Set by SIGTERM/SIGINT handlers to break out of the loop after the
	 * current iteration finishes.
	 *
	 * @var bool
	 */
	protected bool $shouldStop = false;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Schedule due events into the queue for execution.';
	}

	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);
		$parser
			->setDescription(static::getDescription())
			->addOption('dry-run', [
				'boolean' => true,
				'help' => 'List the events that would be scheduled without enqueueing or updating last_run.',
			])
			->addOption('limit', [
				'short' => 'l',
				'help' => 'Cap the number of events dispatched on this tick. The remainder stays due for the next run.',
				'default' => '0',
			])
			->addOption('duration', [
				'help' => 'Loop mode: total seconds to keep scheduling, or "auto" to fill until the next minute boundary. Requires --interval.',
			])
			->addOption('interval', [
				'help' => 'Loop mode: seconds to sleep between scheduling passes. Requires --duration.',
			]);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return int|null The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$duration = $args->getOption('duration');
		$interval = $args->getOption('interval');

		if ($duration === null && $interval === null) {
			return $this->runOnce($args, $io);
		}

		$durationStr = is_string($duration) ? $duration : null;
		$intervalStr = is_string($interval) ? $interval : null;

		$validationError = $this->validateLoopFlags($durationStr, $intervalStr);
		if ($validationError !== null) {
			$io->error($validationError);

			return CommandInterface::CODE_ERROR;
		}

		return $this->runLoop($args, $io, (string)$durationStr, (int)$intervalStr);
	}

	/**
	 * @param string|null $duration Raw --duration option value.
	 * @param string|null $interval Raw --interval option value.
	 *
	 * @return string|null Error message, or null if flags are valid.
	 */
	protected function validateLoopFlags(?string $duration, ?string $interval): ?string {
		if ($duration !== null && $interval === null) {
			return '--duration requires --interval.';
		}
		if ($interval !== null && $duration === null) {
			return '--interval requires --duration.';
		}

		if (!ctype_digit($interval)) {
			return '--interval must be a positive integer (got: ' . var_export($interval, true) . ').';
		}
		$intervalSec = (int)$interval;
		if ($intervalSec < 1) {
			return '--interval must be at least 1 second.';
		}

		if ($duration !== 'auto') {
			if (!ctype_digit($duration)) {
				return '--duration must be a positive integer or "auto" (got: ' . var_export($duration, true) . ').';
			}
			$durationSec = (int)$duration;
			if ($durationSec < $intervalSec) {
				return '--duration must be greater than or equal to --interval.';
			}
		}

		return null;
	}

	/**
	 * Loop mode: schedule repeatedly until the duration window closes.
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $duration "auto" or a positive integer (seconds).
	 * @param int $interval Seconds to sleep between iterations.
	 *
	 * @return int|null
	 */
	protected function runLoop(Arguments $args, ConsoleIo $io, string $duration, int $interval): ?int {
		$lock = $this->createLock();
		$timeout = (int)(Configure::read('QueueScheduler.lockAcquireTimeout') ?? $this->lockAcquireTimeout);
		if (!$lock->acquire($timeout)) {
			$message = sprintf('Scheduler: lock acquire timed out after %ds, exiting', $timeout);
			$io->warning($message);
			$this->log($message, 'warning');

			return null;
		}

		try {
			$this->shouldStop = false;
			$this->installSignalHandlers($io);

			$endTime = $this->computeEndTime($duration);
			$iterations = 0;
			$totalScheduled = 0;
			$totalFailed = 0;

			while (!$this->shouldStop && microtime(true) < $endTime) {
				try {
					$iterStats = $this->scheduleIteration($args, $io);
					$iterations++;
					$totalScheduled += $iterStats['scheduled'];
					$totalFailed += $iterStats['failed'];

					if ($iterStats['total'] > 0 || $iterStats['failed'] > 0) {
						$io->out(sprintf(
							'[iter %d] %d/%d scheduled, %d failed',
							$iterations,
							$iterStats['scheduled'],
							$iterStats['total'],
							$iterStats['failed'],
						));
					}
				} catch (Throwable $e) {
					$iterations++;
					$totalFailed++;
					$this->log(sprintf('Scheduler loop iteration #%d failed: %s', $iterations, $e->getMessage()), 'error');
					$io->error(sprintf('[iter %d] failed: %s', $iterations, $e->getMessage()));
				}

				if (function_exists('pcntl_signal_dispatch')) {
					pcntl_signal_dispatch();
				}

				$remaining = $endTime - microtime(true);
				if ($remaining <= 0) {
					break;
				}
				sleep((int)min($interval, ceil($remaining)));
			}

			$summary = sprintf(
				'Scheduler loop: %d iterations, %d events scheduled, %d failed',
				$iterations,
				$totalScheduled,
				$totalFailed,
			);
			$io->success($summary);
			$this->log($summary, 'info');

			return $totalFailed > 0 ? CommandInterface::CODE_ERROR : null;
		} finally {
			$lock->release();
		}
	}

	/**
	 * @param string $duration "auto" or integer seconds string.
	 *
	 * @return float Unix timestamp (with microsecond precision) when the loop should exit.
	 */
	protected function computeEndTime(string $duration): float {
		$now = microtime(true);
		if ($duration === 'auto') {
			$nextMinuteBoundary = (floor($now / 60) + 1) * 60;

			return $nextMinuteBoundary - 0.5;
		}

		return $now + (int)$duration;
	}

	/**
	 * Run a single scheduling iteration inside the loop. Mirrors runOnce()
	 * but skips the chatty per-tick "N events due" line and returns
	 * structured stats instead of writing a summary.
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return array{total:int,scheduled:int,failed:int}
	 */
	protected function scheduleIteration(Arguments $args, ConsoleIo $io): array {
		$dryRun = (bool)$args->getOption('dry-run');

		$scheduler = $this->createScheduler();
		['events' => $events, 'total' => $total] = $this->prepareEvents($args, $scheduler);

		if ($dryRun) {
			foreach ($events as $row) {
				$io->out(sprintf(' - would dispatch row #%d (%s)', $row->id, $row->name));
			}

			return ['total' => $total, 'scheduled' => $total, 'failed' => 0];
		}

		$scheduled = $scheduler->schedule($events);
		$failed = $scheduler->lastRunFailureCount();

		return ['total' => $total, 'scheduled' => $scheduled, 'failed' => $failed];
	}

	/**
	 * Fetch due events and apply --limit. Used by both runOnce() and the
	 * loop's scheduleIteration().
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \QueueScheduler\Scheduler\Scheduler $scheduler
	 *
	 * @return array{events:\Cake\Collection\CollectionInterface,total:int,uncapped:int,capped:bool}
	 */
	protected function prepareEvents(Arguments $args, Scheduler $scheduler): array {
		$limit = (int)$args->getOption('limit');
		$events = $scheduler->events();
		$uncapped = $events->count();

		if ($limit > 0 && $uncapped > $limit) {
			$events = new Collection(array_slice($events->toArray(), 0, $limit));

			return ['events' => $events, 'total' => $limit, 'uncapped' => $uncapped, 'capped' => true];
		}

		return ['events' => $events, 'total' => $uncapped, 'uncapped' => $uncapped, 'capped' => false];
	}

	/**
	 * Factory seam for the lock.
	 *
	 * @return \QueueScheduler\Scheduler\Lock\LockInterface
	 */
	protected function createLock(): LockInterface {
		$path = Configure::read('QueueScheduler.lockPath');
		if (!is_string($path) || $path === '') {
			$path = TMP . 'queue_scheduler.lock';
		}

		return new FileLock($path);
	}

	/**
	 * Single scheduling pass. Returns null on success, CODE_ERROR if any row threw.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return int|null
	 */
	protected function runOnce(Arguments $args, ConsoleIo $io): ?int {
		$dryRun = (bool)$args->getOption('dry-run');

		$scheduler = $this->createScheduler();
		['events' => $events, 'total' => $total, 'uncapped' => $uncapped, 'capped' => $capped] = $this->prepareEvents($args, $scheduler);

		if ($capped) {
			$io->out(sprintf('%d events due; capping to %d (--limit)', $uncapped, $total));
		} else {
			$io->out(sprintf('%s events due for scheduling', $total));
		}

		if ($dryRun) {
			foreach ($events as $row) {
				$io->out(sprintf(' - would dispatch row #%d (%s)', $row->id, $row->name));
			}
			$io->success(sprintf('Dry run: %d events would have been scheduled.', $total));

			return null;
		}

		$count = $scheduler->schedule($events);
		$failures = $scheduler->lastRunFailureCount();
		$heldBack = $total - $count - $failures;

		$io->success('Done: ' . $count . ' events scheduled.');
		if ($heldBack > 0) {
			$io->warning($heldBack . ' events held back (run not finished or still pending in queue)');
		}
		if ($failures > 0) {
			$io->error(sprintf('%d events failed to schedule (see log)', $failures));
		}

		if ($total > 0 || $failures > 0) {
			$this->log(sprintf('Scheduler: %d/%d events scheduled (%d failed)', $count, $total, $failures), 'info');
		}

		return $failures > 0 ? CommandInterface::CODE_ERROR : null;
	}

	/**
	 * Install SIGTERM/SIGINT handlers if pcntl is available; soft-degrade with
	 * a warning otherwise.
	 *
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return void
	 */
	protected function installSignalHandlers(ConsoleIo $io): void {
		if (!function_exists('pcntl_signal')) {
			$message = 'pcntl extension not loaded; signal handling disabled (loop is still killable with SIGKILL).';
			$io->warning($message);
			$this->log($message, 'warning');

			return;
		}

		$handler = function (): void {
			$this->shouldStop = true;
		};
		pcntl_signal(SIGTERM, $handler);
		pcntl_signal(SIGINT, $handler);
	}

	/**
	 * Factory seam so tests can swap the Scheduler implementation.
	 *
	 * @return \QueueScheduler\Scheduler\Scheduler
	 */
	protected function createScheduler(): Scheduler {
		return new Scheduler();
	}

}
