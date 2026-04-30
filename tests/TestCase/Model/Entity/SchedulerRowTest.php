<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase\Model\Entity;

use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use DateInterval;
use QueueScheduler\Model\Entity\SchedulerRow;

/**
 * Unit tests for SchedulerRow's frequency / due-time calculations.
 *
 * These tests exercise the core scheduling math directly on the entity so
 * regressions in `isDue()`, `calculateNextInterval()`, or `calculateNextRun()`
 * surface here rather than only via the integration tests in SchedulerTest /
 * RunCommandTest.
 *
 * @uses \QueueScheduler\Model\Entity\SchedulerRow
 */
class SchedulerRowTest extends TestCase {

	/**
	 * Reset the frozen test-now to a fresh instance so other test classes that
	 * rely on a non-null `getTestNow()` (e.g. SchedulerRowsTableTest::testInsert)
	 * continue to find one. Clearing it to null instead would leak across files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		DateTime::setTestNow(new DateTime());
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// isDue()
	// -----------------------------------------------------------------------

	/**
	 * @return void
	 */
	public function testIsDueWhenNextRunInPast(): void {
		$row = new SchedulerRow([
			'frequency' => '* * * * *',
			'next_run' => (new DateTime())->subSeconds(60),
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * @return void
	 */
	public function testIsDueWhenNextRunInFuture(): void {
		$row = new SchedulerRow([
			'frequency' => '* * * * *',
			'next_run' => (new DateTime())->addSeconds(60),
		]);

		$this->assertFalse($row->isDue());
	}

	/**
	 * Equal-timestamp boundary: next_run == now must count as due so a row scheduled
	 * exactly on the tick fires immediately rather than a second later.
	 *
	 * @return void
	 */
	public function testIsDueAtNextRunBoundary(): void {
		$now = new DateTime('2026-04-30 12:00:00');
		DateTime::setTestNow($now);

		$row = new SchedulerRow([
			'frequency' => '* * * * *',
			'next_run' => new DateTime('2026-04-30 12:00:00'),
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * Interval frequency with no previous run: due immediately (first execution).
	 *
	 * @return void
	 */
	public function testIsDueForIntervalWithNoLastRun(): void {
		$row = new SchedulerRow([
			'frequency' => '+30 seconds',
			'last_run' => null,
			'next_run' => null,
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * @return void
	 */
	public function testIsDueForIntervalWhenEnoughTimeHasPassed(): void {
		$row = new SchedulerRow([
			'frequency' => '+30 seconds',
			'last_run' => (new DateTime())->subSeconds(60),
			'next_run' => null,
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * @return void
	 */
	public function testIsNotDueForIntervalWhenTooSoon(): void {
		$row = new SchedulerRow([
			'frequency' => '+5 minutes',
			'last_run' => (new DateTime())->subSeconds(60),
			'next_run' => null,
		]);

		$this->assertFalse($row->isDue());
	}

	/**
	 * ISO 8601 duration intervals follow the same code path as `+N unit`.
	 *
	 * @return void
	 */
	public function testIsDueForIsoDurationWhenEnoughTimeHasPassed(): void {
		$row = new SchedulerRow([
			'frequency' => 'PT5M',
			'last_run' => (new DateTime())->subSeconds(600),
			'next_run' => null,
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * @return void
	 */
	public function testIsNotDueForIsoDurationWhenTooSoon(): void {
		$row = new SchedulerRow([
			'frequency' => 'PT5M',
			'last_run' => (new DateTime())->subSeconds(60),
			'next_run' => null,
		]);

		$this->assertFalse($row->isDue());
	}

	/**
	 * Cron `* * * * *` is always due — every minute is a match.
	 *
	 * @return void
	 */
	public function testIsDueForEveryMinuteCron(): void {
		$row = new SchedulerRow([
			'frequency' => '* * * * *',
			'last_run' => null,
			'next_run' => null,
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * @return void
	 */
	public function testIsDueForMinutelyShortcut(): void {
		$row = new SchedulerRow([
			'frequency' => '@minutely',
			'last_run' => null,
			'next_run' => null,
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * Cron `0 0 1 1 *` (midnight on Jan 1) is only due on that exact minute.
	 *
	 * @return void
	 */
	public function testIsNotDueForCronOutsideMatchingMinute(): void {
		DateTime::setTestNow(new DateTime('2026-04-30 12:34:56'));

		$row = new SchedulerRow([
			'frequency' => '0 0 1 1 *',
			'last_run' => null,
			'next_run' => null,
		]);

		$this->assertFalse($row->isDue());
	}

	/**
	 * @return void
	 */
	public function testIsDueForCronAtMatchingMinute(): void {
		DateTime::setTestNow(new DateTime('2026-01-01 00:00:00'));

		$row = new SchedulerRow([
			'frequency' => '0 0 1 1 *',
			'last_run' => null,
			'next_run' => null,
		]);

		$this->assertTrue($row->isDue());
	}

	/**
	 * `next_run` takes priority over the cron schedule — a row that has been
	 * pre-scheduled by the runner should not also re-evaluate its expression.
	 *
	 * @return void
	 */
	public function testNextRunOverridesCronEvaluation(): void {
		DateTime::setTestNow(new DateTime('2026-04-30 12:34:56'));

		$row = new SchedulerRow([
			// Would normally never be due (Jan 1 midnight).
			'frequency' => '0 0 1 1 *',
			// But next_run is set in the past, which forces it due.
			'next_run' => new DateTime('2026-04-30 12:00:00'),
		]);

		$this->assertTrue($row->isDue());
	}

	// -----------------------------------------------------------------------
	// calculateNextInterval()
	// -----------------------------------------------------------------------

	/**
	 * @return void
	 */
	public function testCalculateNextIntervalForRelativeSeconds(): void {
		$row = new SchedulerRow(['frequency' => '+30 seconds']);

		$interval = $row->calculateNextInterval();

		$this->assertInstanceOf(DateInterval::class, $interval);
		$this->assertSame(30, $interval->s);
	}

	/**
	 * @return void
	 */
	public function testCalculateNextIntervalForRelativeMinutes(): void {
		$row = new SchedulerRow(['frequency' => '+5 minutes']);

		$interval = $row->calculateNextInterval();

		$this->assertInstanceOf(DateInterval::class, $interval);
		$this->assertSame(5, $interval->i);
	}

	/**
	 * @return void
	 */
	public function testCalculateNextIntervalForRelativeHours(): void {
		$row = new SchedulerRow(['frequency' => '+2 hours']);

		$interval = $row->calculateNextInterval();

		$this->assertInstanceOf(DateInterval::class, $interval);
		$this->assertSame(2, $interval->h);
	}

	/**
	 * @return void
	 */
	public function testCalculateNextIntervalForIsoDays(): void {
		$row = new SchedulerRow(['frequency' => 'P2D']);

		$interval = $row->calculateNextInterval();

		$this->assertInstanceOf(DateInterval::class, $interval);
		$this->assertSame(2, $interval->d);
	}

	/**
	 * @return void
	 */
	public function testCalculateNextIntervalForIsoTimeComponents(): void {
		$row = new SchedulerRow(['frequency' => 'PT1H30M']);

		$interval = $row->calculateNextInterval();

		$this->assertInstanceOf(DateInterval::class, $interval);
		$this->assertSame(1, $interval->h);
		$this->assertSame(30, $interval->i);
	}

	/**
	 * @return void
	 */
	public function testCalculateNextIntervalReturnsNullForCronExpression(): void {
		$row = new SchedulerRow(['frequency' => '* * * * *']);

		$this->assertNull($row->calculateNextInterval());
	}

	/**
	 * @return void
	 */
	public function testCalculateNextIntervalReturnsNullForCronShortcut(): void {
		$row = new SchedulerRow(['frequency' => '@daily']);

		$this->assertNull($row->calculateNextInterval());
	}

	// -----------------------------------------------------------------------
	// calculateNextRun()
	// -----------------------------------------------------------------------

	/**
	 * Interval-based row with no last_run: first run is "now" (the runner will
	 * dispatch on the next tick).
	 *
	 * @return void
	 */
	public function testCalculateNextRunForIntervalWithNoLastRun(): void {
		$now = new DateTime('2026-04-30 12:00:00');
		DateTime::setTestNow($now);

		$row = new SchedulerRow([
			'frequency' => '+30 seconds',
			'last_run' => null,
		]);

		$nextRun = $row->calculateNextRun();

		$this->assertInstanceOf(DateTime::class, $nextRun);
		$this->assertSame($now->timestamp, $nextRun->timestamp);
	}

	/**
	 * Interval-based row with a previous run: next is last_run + interval.
	 *
	 * @return void
	 */
	public function testCalculateNextRunForIntervalAddsToLastRun(): void {
		$lastRun = new DateTime('2026-04-30 12:00:00');

		$row = new SchedulerRow([
			'frequency' => '+30 seconds',
			'last_run' => $lastRun,
		]);

		$nextRun = $row->calculateNextRun();

		$this->assertInstanceOf(DateTime::class, $nextRun);
		$this->assertSame($lastRun->timestamp + 30, $nextRun->timestamp);
	}

	/**
	 * @return void
	 */
	public function testCalculateNextRunForIsoDurationAddsToLastRun(): void {
		$lastRun = new DateTime('2026-04-30 12:00:00');

		$row = new SchedulerRow([
			'frequency' => 'P1D',
			'last_run' => $lastRun,
		]);

		$nextRun = $row->calculateNextRun();

		$this->assertInstanceOf(DateTime::class, $nextRun);
		$this->assertSame((string)$lastRun->addDays(1), (string)$nextRun);
	}

	/**
	 * Cron expression resolves to the next matching slot. We don't pin a specific
	 * time here because CronExpression::getNextRunDate() uses real system time
	 * (not Cake's setTestNow), so we assert the shape of the result instead.
	 *
	 * @return void
	 */
	public function testCalculateNextRunForCronExpression(): void {
		$row = new SchedulerRow([
			'frequency' => '0 11 * * *',
			'last_run' => null,
		]);

		$nextRun = $row->calculateNextRun();

		$this->assertInstanceOf(DateTime::class, $nextRun);
		// Cron `0 11 * * *` resolves to 11:00 on some date.
		$this->assertSame(11, (int)$nextRun->format('G'));
		$this->assertSame(0, (int)$nextRun->format('i'));
		$this->assertSame(0, (int)$nextRun->format('s'));
	}

	/**
	 * `@minutely` is normalized to `* * * * *`. The result is the next zero-second
	 * minute boundary; we allow a small skew window since CronExpression evaluates
	 * against real wall time.
	 *
	 * @return void
	 */
	public function testCalculateNextRunForMinutelyShortcut(): void {
		$row = new SchedulerRow([
			'frequency' => '@minutely',
			'last_run' => null,
		]);

		$nextRun = $row->calculateNextRun();
		// CronExpression evaluates against real wall-clock time (not setTestNow),
		// so we compare against time() rather than `new DateTime()` which respects
		// the bootstrap-frozen Chronos::now().
		$now = time();

		$this->assertInstanceOf(DateTime::class, $nextRun);
		// Result is on a minute boundary.
		$this->assertSame(0, (int)$nextRun->format('s'));
		// And within ~2 minutes of real now (covers normal "next minute" plus clock skew).
		$delta = $nextRun->timestamp - $now;
		$this->assertGreaterThanOrEqual(0, $delta);
		$this->assertLessThanOrEqual(120, $delta);
	}

	/**
	 * An invalid cron expression must yield null rather than throw — the
	 * scheduler treats null as "skip this row" so a single bad row cannot
	 * block the batch.
	 *
	 * @return void
	 */
	public function testCalculateNextRunReturnsNullForInvalidCron(): void {
		$row = new SchedulerRow([
			'frequency' => 'this is not a cron expression',
			'last_run' => null,
		]);

		$this->assertNull($row->calculateNextRun());
	}

}
