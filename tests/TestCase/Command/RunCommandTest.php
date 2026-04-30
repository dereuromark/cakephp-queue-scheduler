<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * QueueScheduler\Command\RunCommand Test Case
 *
 * @uses \QueueScheduler\Command\RunCommand
 */
class RunCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.QueueScheduler.SchedulerRows',
	];

	/**
	 * @return void
	 */
	public function testRun(): void {
		$this->exec('scheduler run');

		$this->assertExitCode(0);
		$this->assertOutputContains('1 events due for scheduling');
		$this->assertOutputContains('Done: 1 events scheduled');
	}

	/**
	 * --dry-run should list events without enqueueing them or updating last_run.
	 *
	 * @return void
	 */
	public function testRunDryRun(): void {
		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');
		$beforeLastRun = $rowsTable->get(1)->last_run;

		$this->exec('scheduler run --dry-run');

		$this->assertExitCode(0);
		$this->assertOutputContains('would dispatch row #1');
		$this->assertOutputContains('Dry run: 1 events would have been scheduled.');
		$this->assertOutputNotContains('Done:');

		// last_run untouched.
		$this->assertEquals($beforeLastRun, $rowsTable->get(1)->last_run);
	}

	/**
	 * --limit caps the number of events dispatched.
	 *
	 * @return void
	 */
	public function testRunWithLimit(): void {
		$rowsTable = $this->fetchTable('QueueScheduler.SchedulerRows');
		// Add a second due row so the cap actually kicks in.
		$extra = $rowsTable->newEntity([
			'name' => 'Second due row',
			'type' => 1,
			'content' => 'Cake\\Command\\CacheClearCommand',
			'frequency' => '+30seconds',
			'enabled' => true,
		]);
		// Backdate next_run so the row is due now.
		$extra->next_run = (new DateTime())->subSeconds(60);
		$rowsTable->saveOrFail($extra);

		$this->exec('scheduler run --limit=1 --dry-run');

		$this->assertExitCode(0);
		$this->assertOutputContains('2 events due; capping to 1 (--limit)');
		$this->assertOutputContains('Dry run: 1 events would have been scheduled.');
	}

}
