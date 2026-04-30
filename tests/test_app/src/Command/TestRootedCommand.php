<?php declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

/**
 * Test command with a pre-formatted, non-`cake` rooted name.
 *
 * CommandExecuteTask must leave names that already contain a space alone —
 * a non-`cake` root like `app foo` must NOT be rewritten to `cake foo`.
 * execute() emits the live `getName()` so the test can assert on the
 * actual name observed during the run rather than just on output forwarding.
 */
class TestRootedCommand extends Command {

	/**
	 * @var string
	 */
	protected string $name = 'app rooted';

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|null
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$io->out('name=' . $this->getName());

		return static::CODE_SUCCESS;
	}

}
