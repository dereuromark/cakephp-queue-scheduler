<?php

namespace QueueScheduler\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Model\QueueException;
use QueueScheduler\Queue\Task\CommandExecuteTask;
use TestApp\Command\TestOutputCommand;
use Tools\Command\InflectCommand;

class CommandExecuteTaskTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRunStdoutForwarded(): void {
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
	public function testRunStderrForwarded(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = [
			'class' => TestOutputCommand::class,
			'args' => [],
		];
		$task->run($data, 0);

		$this->assertTextContains('stderr warning line', $err->output());
	}

	/**
	 * @return void
	 */
	public function testRunCombinedOutput(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = [
			'class' => TestOutputCommand::class,
			'args' => [],
		];
		$task->run($data, 0);

		$stdout = $out->output();
		$this->assertTextContains('stdout line one', $stdout);
		$this->assertTextContains('stdout line two', $stdout);
		$this->assertTextContains('stderr warning line', $err->output());
	}

	/**
	 * @return void
	 */
	public function testRunFailureThrowsException(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = [
			'class' => TestOutputCommand::class,
			'args' => ['--fail'],
		];

		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('TestOutputCommand');
		$task->run($data, 0);
	}

	/**
	 * @return void
	 */
	public function testRunFailureStillForwardsOutput(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = [
			'class' => TestOutputCommand::class,
			'args' => ['--fail'],
		];

		try {
			$task->run($data, 0);
		} catch (QueueException) {
			// Expected
		}

		// Output should still be forwarded before the exception
		$this->assertTextContains('stdout line one', $out->output());
		$this->assertTextContains('stderr warning line', $err->output());
	}

}
