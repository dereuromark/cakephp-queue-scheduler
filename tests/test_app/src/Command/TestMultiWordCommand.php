<?php declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

/**
 * Test command with a multi-word name (space-separated) — mirroring how
 * CakePHP core registers commands like `cache clear`, `plugin assets symlink`,
 * or this plugin's own `scheduler run` (see QueueSchedulerPlugin::console()).
 *
 * CommandExecuteTask must NOT rewrite names that already contain a space.
 * BaseCommand::getOptionParser() only splits on the first space and uses
 * the second segment as the parser name, so any spaced name parses fine —
 * but rewriting `cache clear` to `cake <defaultName>` would corrupt the
 * command identity (defaultName() returns the leaf token only, losing the
 * namespace prefix). execute() emits the live getName() so the test can
 * assert the name observed at run time is unchanged.
 */
class TestMultiWordCommand extends Command {

	/**
	 * @var string
	 */
	protected string $name = 'cache clear';

	/**
	 * @return int|null
	 */
	public function execute(): ?int {
		$io = $this->io;
		$io->out('name=' . $this->getName());

		return static::CODE_SUCCESS;
	}

}
