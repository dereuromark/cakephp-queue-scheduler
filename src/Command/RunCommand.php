<?php declare(strict_types=1);

namespace QueueScheduler\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use QueueScheduler\Scheduler\Scheduler;

/**
 * Run command.
 */
class RunCommand extends Command {

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

		$io->out(sprintf('%s events due for scheduling', $events->count()));

		$count = $scheduler->schedule($events);

		$io->success('Done: ' . $count . ' events scheduled.');
		if ($count < $events->count()) {
			$io->warning($events->count() - $count . ' events held back (run not finished or still pending in queue)');
		}

		return null;
	}

}
