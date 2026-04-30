<?php declare(strict_types=1);

namespace QueueScheduler\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Log\LogTrait;
use QueueScheduler\Scheduler\Scheduler;

/**
 * Run command.
 */
class RunCommand extends Command {

	use LogTrait;

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Schedule due events into the queue for execution.';
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return int|null The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$scheduler = new Scheduler();
		$events = $scheduler->events();
		$total = $events->count();

		$io->out(sprintf('%s events due for scheduling', $total));

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

		$this->log(sprintf('Scheduler: %d/%d events scheduled (%d failed)', $count, $total, $failures), 'info');

		return $failures > 0 ? CommandInterface::CODE_ERROR : null;
	}

}
