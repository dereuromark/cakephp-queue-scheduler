<?php

namespace QueueScheduler\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use QueueScheduler\Utility\CommandFinder;
use Tools\Command\InflectCommand;

class CommandFinderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testAll() {
		$result = (new CommandFinder())->all();
		unset($result['Queue.MigrateTasks']);
		$this->assertNotEmpty($result);
		$this->assertSame($result['Tools.Inflect'], InflectCommand::class);
	}

}
