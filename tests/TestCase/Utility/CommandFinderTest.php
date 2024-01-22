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
		$expected = [
			'Queue.BakeQueueTask' => 'Queue.BakeQueueTask',
			'Queue.Worker' => 'Queue.Worker',
			'Queue.Job' => 'Queue.Job',
			'Queue.Run' => 'Queue.Run',
			'Queue.Add' => 'Queue.Add',
			'Queue.MigrateTasks' => 'Queue.MigrateTasks',
			'Queue.Info' => 'Queue.Info',
			'QueueScheduler.Run' => 'QueueScheduler.Run',
			'Tools.Inflect' => 'Tools.Inflect',
		];
		$this->assertEquals($expected, $result);
	}

}
