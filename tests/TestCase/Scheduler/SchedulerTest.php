<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Scheduler;

use Cake\Collection\Collection;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;
use QueueScheduler\Scheduler\Scheduler;
use RuntimeException;

class SchedulerTest extends TestCase {

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// ConsoleIntegrationTestTrait (used by RunCommandTest) leaves a
		// ConsoleLog handler bound to a now-closed stdout stream; drop any
		// stale stdout/stderr handlers so this test's log() call doesn't
		// blow up writing to a dead resource.
		foreach (['stdout', 'stderr'] as $key) {
			if (Log::getConfig($key) !== null) {
				Log::drop($key);
			}
		}
	}

	/**
	 * A throwing row must not abort the batch; remaining rows still dispatch
	 * and the failure is reported via lastRunFailureCount().
	 *
	 * @return void
	 */
	public function testScheduleSwallowsRowFailures(): void {
		$rowOk = new SchedulerRow(['id' => 1, 'name' => 'ok']);
		$rowBad = new SchedulerRow(['id' => 2, 'name' => 'bad']);

		$rowsTable = $this->createStub(SchedulerRowsTable::class);
		$rowsTable->method('run')->willReturnCallback(function (SchedulerRow $row): bool {
			if ($row->id === 2) {
				throw new RuntimeException('boom');
			}

			return true;
		});

		$scheduler = new Scheduler();
		$scheduler->getTableLocator()->set('QueueScheduler.SchedulerRows', $rowsTable);

		$count = $scheduler->schedule(new Collection([$rowOk, $rowBad]));

		$this->assertSame(1, $count);
		$this->assertSame(1, $scheduler->lastRunFailureCount());
	}

	/**
	 * Held-back rows (run() returns false) must not be counted as failures.
	 *
	 * @return void
	 */
	public function testScheduleDoesNotCountHeldBackAsFailure(): void {
		$row = new SchedulerRow(['id' => 1, 'name' => 'held']);

		$rowsTable = $this->createStub(SchedulerRowsTable::class);
		$rowsTable->method('run')->willReturn(false);

		$scheduler = new Scheduler();
		$scheduler->getTableLocator()->set('QueueScheduler.SchedulerRows', $rowsTable);

		$count = $scheduler->schedule(new Collection([$row]));

		$this->assertSame(0, $count);
		$this->assertSame(0, $scheduler->lastRunFailureCount());
	}

}
