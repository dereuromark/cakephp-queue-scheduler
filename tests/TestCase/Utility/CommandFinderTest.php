<?php

namespace QueueScheduler\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use QueueScheduler\Utility\CommandFinder;
use Tools\Command\InflectCommand;

class CommandFinderTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		CommandFinder::clearCache();
	}

	/**
	 * @return void
	 */
	public function testAll() {
		$result = (new CommandFinder())->all();
		unset($result['Queue.MigrateTasks']);
		$this->assertNotEmpty($result);
		$this->assertSame($result['Tools.Inflect'], InflectCommand::class);
	}

	/**
	 * @return void
	 */
	public function testAllReturnsSameInstanceCached(): void {
		$finder = new CommandFinder();
		$first = $finder->all();
		$second = $finder->all();

		$this->assertSame($first, $second);
	}

}
