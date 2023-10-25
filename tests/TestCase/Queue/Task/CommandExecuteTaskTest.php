<?php

namespace QueueScheduler\Test\TestCase\Queue\Task;

use Cake\Console\Command\HelpCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;
use QueueScheduler\Queue\Task\CommandExecuteTask;
use Tools\Command\InflectCommand;

class CommandExecuteTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$task = new CommandExecuteTask();

		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$data = [
			'class' => InflectCommand::class,
			'args' => ['Foo', 'pluralize'],
			'io' => new ConsoleIo($out, $err),
		];
		$task->run($data, 0);

		$this->assertTextContains('Foos', $out->output());
	}

}
