<?php declare(strict_types=1);

namespace QueueScheduler\Test\TestCase;

use Cake\I18n\DateTime;

/**
 * Restores the frozen test-now after a test has temporarily overridden it.
 */
trait ResetsTestNowTrait {

	/**
	 * Restore the global test-now to the real wall clock as a frozen, non-null value.
	 *
	 * `DateTime::setTestNow(new DateTime())` on its own is not enough: while an
	 * override is active, `new DateTime()` resolves to the frozen value, so a test
	 * that pinned a past date would re-freeze that past date and leak it into later
	 * tests (RowTest::testCalculateNextRunForCronWithoutLastRun then computes a
	 * "next run" in the past and fails). Clearing the override first lets
	 * `new DateTime()` read the wall clock again; re-freezing keeps it non-null for
	 * tests that depend on getTestNow() (SchedulerRowsTableTest::testInsert).
	 *
	 * @return void
	 */
	protected function resetTestNow(): void {
		DateTime::setTestNow(null);
		DateTime::setTestNow(new DateTime());
	}

}
