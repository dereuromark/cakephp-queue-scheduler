<?php

namespace QueueScheduler\Test\TestCase\Queue\Task;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Queue\Console\Io;
use Queue\Model\QueueException;
use QueueScheduler\Queue\Task\CommandExecuteTask;
use TestApp\Command\TestMultiWordCommand;
use TestApp\Command\TestNamedCommand;
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
	public function testRunRejectsUnknownClass(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = ['class' => 'NotARealClass\\DoesNotExist'];

		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Invalid command class');
		$task->run($data, 0);
	}

	/**
	 * @return void
	 */
	public function testRunRejectsNonCommandClass(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = ['class' => static::class];

		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Invalid command class');
		$task->run($data, 0);
	}

	/**
	 * @return void
	 */
	public function testRunRejectsMissingClassKey(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$this->expectException(QueueException::class);
		$this->expectExceptionMessage('Invalid command class');
		$task->run([], 0);
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

	/**
	 * Regression: most app commands set `$name` to a single bare token
	 * (e.g. `'clear_sessions'`), expecting `Cake\Console\CommandRunner`
	 * to rewrite it to `"cake clear_sessions"` before running. When the
	 * scheduler invokes such a command, CommandExecuteTask must do the
	 * same — otherwise BaseCommand::getOptionParser() does
	 * `explode(' ', $this->name, 2)`, returns a null second element, and
	 * `new ConsoleOptionParser(null)` throws a TypeError.
	 *
	 * @return void
	 */
	public function testRunPrefixesCommandNameForOptionParser(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		$data = [
			'class' => TestNamedCommand::class,
			'args' => [],
		];

		// Without the setName fix this throws TypeError, not QueueException.
		$task->run($data, 0);

		$this->assertTextContains('test_named ran', $out->output());
	}

	/**
	 * Counterpart to the regression above: commands whose name already
	 * contains a space — multi-word commands like `cache clear`,
	 * `plugin assets symlink`, or this plugin's own `scheduler run` — must
	 * NOT be rewritten. Rewriting would call `setName('cake ' . defaultName())`
	 * which returns only the leaf token (e.g. `cake clear`), corrupting the
	 * command's identity by dropping the namespace prefix.
	 *
	 * The test command emits its live `getName()` so we assert on the actual
	 * name observed during execution. A regression that re-enabled rewriting
	 * on multi-word names would surface here.
	 *
	 * @return void
	 */
	public function testRunDoesNotRewriteMultiWordCommandName(): void {
		$out = new StubConsoleOutput();
		$err = new StubConsoleOutput();
		$io = new Io(new ConsoleIo($out, $err));
		$task = new CommandExecuteTask($io);

		// TestMultiWordCommand sets $name to "cache clear" and prints its
		// live getName(). The task must leave it alone.
		$data = [
			'class' => TestMultiWordCommand::class,
			'args' => [],
		];
		$task->run($data, 0);

		$this->assertTextContains('name=cache clear', $out->output());
		$this->assertTextNotContains('name=cake clear', $out->output());
	}

}
