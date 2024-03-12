<?php

namespace QueueScheduler\Test\TestCase\Utility;

use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Queue\QueuePlugin;
use QueueScheduler\Utility\CommandFinder;

class CommandFinderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testAll() {
		Plugin::getCollection()->add(new QueuePlugin());

		$result = (new CommandFinder())->all();
		unset($result['Queue.MigrateTasks']);

		$keys = array_keys($result);
		$expected = [
			'Queue.BakeQueueTask',
			'Queue.Worker',
			'Queue.Job',
			'Queue.Run',
			'Queue.Add',
			'Queue.Info',
			'QueueScheduler.Run',
			'Tools.Inflect',
		];
		$this->assertEquals($expected, $keys);
	}

}
