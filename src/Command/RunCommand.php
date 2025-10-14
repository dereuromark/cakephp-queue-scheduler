<?php declare(strict_types=1);

namespace QueueScheduler\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use QueueScheduler\Scheduler\Scheduler;
use QueueScheduler\Trait\CommandLoggerTrait;

/**
 * Run command.
 */
class RunCommand extends Command {

	use CommandLoggerTrait;

	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 *
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('no-logging', [
			'help' => 'Disable logging for this run',
			'boolean' => true,
			'default' => false,
		]);

		$parser->addOption('log-level', [
			'help' => 'Set the logging verbosity level',
			'choices' => ['minimal', 'normal', 'verbose'],
			'default' => 'normal',
		]);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 *
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		// Initialize logging
		$loggingConfig = [
			'enabled' => !$args->getOption('no-logging'),
		];
		$loggingIo = $this->initializeLogging($io, $loggingConfig);

		$startTime = microtime(true);

		$scheduler = new Scheduler();
		$events = $scheduler->events();

		$loggingIo->out(sprintf('%s events due for scheduling', $events->count()));

		$count = $scheduler->schedule($events);

		$loggingIo->success('Done: ' . $count . ' events scheduled.');
		if ($count < $events->count()) {
			$loggingIo->warning($events->count() - $count . ' events held back (run not finished or still pending in queue)');
		}

		$executionTime = round(microtime(true) - $startTime, 3);

		// Save logs with metadata
		$metadata = [
			'events_due' => $events->count(),
			'events_scheduled' => $count,
			'events_held_back' => $events->count() - $count,
			'execution_time' => $executionTime,
			'log_level' => $args->getOption('log-level'),
		];

		$this->saveCommandLogs('scheduler:run', $args, $metadata);
	}

}
