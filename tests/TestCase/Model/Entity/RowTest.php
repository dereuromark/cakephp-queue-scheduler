<?php
declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Entity;

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

}
