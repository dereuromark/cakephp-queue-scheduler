<?php declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Adds time-window constraint columns to queue_scheduler_rows so a
 * cron-like row can additionally be gated on time-of-day or day-of-week.
 *
 * Cron expressions already handle "every Monday at 9" but can't express
 * "every 5 min, but only between 09:00-18:00 on weekdays" — those need
 * compound restrictions ANDed against the firing decision. This migration
 * adds the columns; the runtime check lives on SchedulerRow::isDue().
 *
 * All three columns are nullable; a row without any restriction column
 * set behaves exactly as before (no extra gating).
 */
class QueueSchedulerTimeWindow extends BaseMigration {

	/**
	 * @return void
	 */
	public function change(): void {
		$this->table('queue_scheduler_rows')
			->addColumn('window_start_time', 'time', [
				'null' => true,
				'default' => null,
				'comment' => 'Earliest time-of-day (server timezone) at which this row may dispatch. '
					. 'Null = no lower bound.',
			])
			->addColumn('window_end_time', 'time', [
				'null' => true,
				'default' => null,
				'comment' => 'Latest time-of-day (server timezone) at which this row may dispatch. '
					. 'Null = no upper bound. If both window_start_time and '
					. 'window_end_time are set the comparison wraps midnight when '
					. 'end < start (e.g. 22:00-06:00 means "overnight").',
			])
			->addColumn('window_days_of_week', 'string', [
				'limit' => 32,
				'null' => true,
				'default' => null,
				'comment' => 'Comma-separated 0-6 days of week (0=Sunday, 6=Saturday) on which this '
					. 'row may dispatch. Null = every day. Example: "1,2,3,4,5" for weekdays only.',
			])
			->update();
	}

}
