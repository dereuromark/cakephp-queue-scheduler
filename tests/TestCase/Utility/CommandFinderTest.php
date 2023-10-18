<?php

namespace QueueScheduler\Test\TestCase\Utility;

use Cake\Core\Plugin as CakePlugin;
use Cake\TestSuite\TestCase;
use Queue\Plugin;
use QueueScheduler\Utility\CommandFinder;

class CommandFinderTest extends TestCase {

	/**
	 * @return void
	 */
	public function testAll() {
		CakePlugin::getCollection()->add(new Plugin());

		$result = (new CommandFinder())->all();
		$expected = [
			'Queue.BakeQueueTask' => 'Queue.BakeQueueTask',
			'Queue.Worker' => 'Queue.Worker',
			'Queue.Job' => 'Queue.Job',
			'Queue.Run' => 'Queue.Run',
			'Queue.Add' => 'Queue.Add',
			'Queue.MigrateTasks' => 'Queue.MigrateTasks',
			'Queue.Info' => 'Queue.Info',
		];
		$this->assertEquals($expected, $result);
	}

}
