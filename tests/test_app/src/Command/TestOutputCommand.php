<?php declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Test command that writes to both stdout and stderr.
 */
class TestOutputCommand extends Command {

	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser->addOption('fail', [
			'boolean' => true,
			'default' => false,
		]);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|null
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$io->out('stdout line one');
		$io->out('stdout line two');
		$io->err('stderr warning line');

		if ($args->getOption('fail')) {
			return static::CODE_ERROR;
		}

		return static::CODE_SUCCESS;
	}

}
