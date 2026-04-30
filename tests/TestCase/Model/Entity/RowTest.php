<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Entity;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Queue\Queue\Task\ExampleTask;
use QueueScheduler\Model\Entity\SchedulerRow;

class RowTest extends TestCase {

	/**
	 * @return void
	 */
	public function testJobTask(): void {
		$row = new SchedulerRow();
		$row->content = ExampleTask::class;
		$row->type = $row::TYPE_QUEUE_TASK;

		$result = $row->job_task;
		$this->assertSame('Queue.Example', $result);
	}

	/**
	 * @return void
	 */
	public function testJobTaskShell(): void {
		$row = new SchedulerRow();
		$row->content = 'uname';
		$row->type = $row::TYPE_SHELL_COMMAND;

		$this->assertSame('Queue.Execute', $row->job_task);
		$this->assertEquals(['command' => 'uname'], $row->job_data);
	}

	/**
	 * A cron-expression row with no last_run should fire at the NEXT cron
	 * occurrence, not immediately on the next scheduler tick.
	 *
	 * @return void
	 */
	public function testCalculateNextRunForCronWithoutLastRun(): void {
		$row = new SchedulerRow();
		$row->frequency = '0 11 * * *';
		$row->last_run = null;

		$nextRun = $row->calculateNextRun();
		$this->assertNotNull($nextRun);
		// Cron library uses native DateTime, so we cannot pin "now" via Chronos
		// — just assert the result is a strictly future 11:00 occurrence.
		$this->assertSame('11:00:00', $nextRun->format('H:i:s'));
		$this->assertGreaterThan(time(), $nextRun->timestamp);
	}

	/**
	 * Interval-based rows keep their existing first-run-immediately behavior.
	 *
	 * @return void
	 */
	public function testCalculateNextRunForIntervalWithoutLastRun(): void {
		$row = new SchedulerRow();
		$row->frequency = '+30seconds';
		$row->last_run = null;

		$this->assertNotNull($row->calculateNextRun());
	}

	/**
	 * isDue() must consider a row whose next_run equals "now" as due,
	 * matching the SQL filter `next_run <= NOW()` in findScheduled().
	 *
	 * @return void
	 */
	public function testIsDueAtBoundary(): void {
		$row = new SchedulerRow();
		$row->frequency = '@daily';
		// next_run set to "now" — must be considered due (matches SQL `<=`).
		$row->next_run = new DateTime();

		$this->assertTrue($row->isDue());
	}

}
