<?php declare(strict_types=1);

namespace QueueScheduler\Command;

use Cake\Collection\Collection;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
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
		$dryRun = (bool)$args->getOption('dry-run');
		$limit = (int)$args->getOption('limit');

		$scheduler = new Scheduler();
		$events = $scheduler->events();
		$total = $events->count();

		if ($limit > 0 && $total > $limit) {
			$events = new Collection(array_slice($events->toArray(), 0, $limit));
			$io->out(sprintf('%d events due; capping to %d (--limit)', $total, $limit));
			$total = $limit;
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

		$this->log(sprintf('Scheduler: %d/%d events scheduled (%d failed)', $count, $total, $failures), 'info');

		return $failures > 0 ? CommandInterface::CODE_ERROR : null;
	}

}
