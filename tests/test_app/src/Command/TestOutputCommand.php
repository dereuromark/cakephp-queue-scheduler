<?php declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\ConsoleOptionParser;
use RuntimeException;

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

		$parser->addOption('throw', ['boolean' => true]);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIoInterface $io
	 * @return int|null
	 */
	public function execute(): ?int {
		$args = $this->args;
		$io = $this->io;
		$io->out('stdout line one');
		$io->out('stdout line two');
		$io->err('stderr warning line');

		if ($args->getOption('throw')) {
			throw new RuntimeException('Original command failure');
		}

		if ($args->getOption('fail')) {
			return static::CODE_ERROR;
		}

		return static::CODE_SUCCESS;
	}

}
