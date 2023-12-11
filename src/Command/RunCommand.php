<?php declare(strict_types=1);

namespace QueueScheduler\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use QueueScheduler\Scheduler\Scheduler;

/**
 * Run command.
 */
class RunCommand extends Command {

	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 *
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$scheduler = new Scheduler();
		$events = $scheduler->events();

		$io->out(sprintf('%s events due for scheduling', $events->count()));

		$count = $scheduler->schedule($events);

		$io->success('Done: ' . $count . ' events scheduled.');
		if ($count < $events->count()) {
			$io->warning($events->count() - $count . ' events held back (run not finished or still pending in queue)');
		}
	}

}
