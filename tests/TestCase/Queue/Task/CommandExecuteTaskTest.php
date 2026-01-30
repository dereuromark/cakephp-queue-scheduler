<?php

namespace QueueScheduler\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Model\QueueException;
use QueueScheduler\Queue\Task\CommandExecuteTask;
use Tools\Command\InflectCommand;

class CommandExecuteTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRun(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = [
			'class' => InflectCommand::class,
			'args' => ['Foo', 'pluralize'],
		];
		$task->run($data, 0);

		$this->assertTextContains('Foos', $out->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailure(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = [
			'class' => InflectCommand::class,
			'args' => ['--invalid-arg'],
		];

		$this->expectException(QueueException::class);
		$task->run($data, 0);
	}

}
