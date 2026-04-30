<?php declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Test command that follows the common app convention of overriding
 * `$name` to a single bare token (no `cake ` prefix). This is what
 * `Cake\Console\CommandRunner` would normally rewrite to `"cake foo"`
 * via `setName()` before running. CommandExecuteTask must do the same,
 * otherwise BaseCommand::getOptionParser() throws a TypeError when it
 * tries to `explode(' ', $this->name, 2)`.
 */
class TestNamedCommand extends Command {

	/**
	 * @var string
	 */
	protected string $name = 'test_named';

	/**
	 * @return string
	 */
	public static function defaultName(): string {
		return 'test_named';
	}

	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		return $parser->setDescription('test_named');
	}

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|null
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$io->out('test_named ran');

		return static::CODE_SUCCESS;
	}

}
