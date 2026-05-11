<?php declare(strict_types=1);

namespace QueueScheduler\Model\Entity;

use Cake\I18n\DateTime;
use Cron\CronExpression;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Locale;
use Panlatent\CronExpressionDescriptor\ExpressionDescriptor;
use Queue\Queue\Config;
use RuntimeException;
use Tools\Model\Entity\Entity;

/**
 * QueueSchedulerRow Entity
 *
 * @property int $id
 * @property string $name
 * @property int $type
 * @property string $content
 * @property string|null $param
 * @property string $frequency
 * @property \Cake\I18n\DateTime|null $last_run
 * @property \Cake\I18n\DateTime|null $next_run
 * @property mixed $window_start_time
 * @property mixed $window_end_time
 * @property string|null $window_days_of_week
 * @property bool $allow_concurrent
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property bool $enabled
 * @property int|null $last_queued_job_id
 * @property \Queue\Model\Entity\QueuedJob|null $last_queued_job
 * @property-read string|null $job_task
 * @property-read array $job_data
 * @property-read array<string, mixed> $job_config
 * @property-read string $job_reference
 */
class SchedulerRow extends Entity {

	/**
	 * @var array<string>
	 */
	protected static array $shortcuts = [
		'@yearly',
		'@monthly',
		'@weekly',
		'@daily',
		'@hourly',
		'@minutely',
	];

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'*' => true,
		'id' => false,
	];

	/**
	 * @param array<int>|int|null $value
	 *
	 * @return array<string, string>|string
	 */
	public static function types(array|int|null $value = null): array|string {
		$options = [
			static::TYPE_QUEUE_TASK => __d('queue_scheduler', 'Queue Task'),
			static::TYPE_CAKE_COMMAND => __d('queue_scheduler', 'Cake Command'),
			static::TYPE_SHELL_COMMAND => __d('queue_scheduler', 'Shell Command (raw command execution)'),
		];

		/** @var array<string, string>|string */
		return parent::enum($value, $options);
	}

	/**
	 * @var int
	 */
	public const TYPE_SHELL_COMMAND = 2;

	/**
	 * @var int
	 */
	public const TYPE_CAKE_COMMAND = 1;

	/**
	 * @var int
	 */
	public const TYPE_QUEUE_TASK = 0;

	/**
	 * @return bool
	 */
	public function isDue(): bool {
		$nextRun = $this->next_run;
		$lastRun = $this->last_run;

		$dateTime = new DateTime();

		// Time-window gate is ANDed against the cron/interval firing. A row
		// whose schedule says "now" but whose window says "not until 09:00"
		// must defer; the next tick after 09:00 will re-evaluate and fire.
		if (!$this->isWithinWindow($dateTime)) {
			return false;
		}

		if ($nextRun) {
			// Don't trust `next_run` alone — if a previous tick already executed
			// this row but the dispatcher crashed before advancing `next_run`,
			// the row would re-fire on every subsequent tick. Once `last_run`
			// has caught up to (or past) the scheduled slot, ignore the stale
			// `next_run` and fall through to recompute from the frequency.
			if ($lastRun === null || $lastRun->timestamp < $nextRun->timestamp) {
				return $nextRun->timestamp <= $dateTime->timestamp;
			}
		}

		$nextInterval = $this->calculateNextInterval();
		if ($nextInterval) {
			if ($lastRun === null) {
				return true;
			}

			return $lastRun->add($nextInterval)->timestamp <= $dateTime->timestamp;
		}

		return (new CronExpression(static::normalizeCronExpression($this->frequency)))->isDue($dateTime->toDateTimeString());
	}

	/**
	 * Check whether the row's optional time-window restrictions allow
	 * dispatch at the given moment.
	 *
	 * Three independent restrictions, all optional and ANDed together:
	 * - `window_days_of_week`: comma-separated 0–6 days (0=Sunday, 6=Saturday).
	 *   Null → every day.
	 * - `window_start_time` / `window_end_time`: time-of-day bounds in the
	 *   server's timezone. Wraps midnight when end < start (so 22:00–06:00
	 *   means "overnight" rather than "always blocked").
	 *
	 * Rows that pre-date the time-window migration have all three columns
	 * null → method returns true unconditionally. The column-access via
	 * `get()` tolerates a row whose entity class hasn't been re-fetched
	 * after the migration.
	 *
	 * @param \Cake\I18n\DateTime $when
	 * @return bool
	 */
	public function isWithinWindow(DateTime $when): bool {
		$allowed = $this->allowedWindowDays();
		if (is_string($this->get('window_days_of_week')) && $this->get('window_days_of_week') !== '' && $allowed === null) {
			return false;
		}
		if ($allowed !== null) {
			$dow = (int)$when->format('w'); // 0 (Sun) – 6 (Sat)
			if (!in_array($dow, $allowed, true)) {
				return false;
			}
		}

		$start = $this->get('window_start_time');
		$end = $this->get('window_end_time');
		if ($start === null && $end === null) {
			return true;
		}

		$nowMinutes = ((int)$when->format('G')) * 60 + (int)$when->format('i');
		$startMinutes = $start !== null ? static::timeToMinutes($start) : null;
		$endMinutes = $end !== null ? static::timeToMinutes($end) : null;
		if (($start !== null && $startMinutes === null) || ($end !== null && $endMinutes === null)) {
			return false;
		}

		if ($startMinutes !== null && $endMinutes === null) {
			return $nowMinutes >= $startMinutes;
		}
		if ($startMinutes === null && $endMinutes !== null) {
			return $nowMinutes <= $endMinutes;
		}

		// Both bounds set. Overnight window (end < start) treats the
		// interval as wrapping midnight, so 22:00–06:00 allows 23:30 and
		// 02:00 but not 12:00.
		if ($endMinutes < $startMinutes) {
			return $nowMinutes >= $startMinutes || $nowMinutes <= $endMinutes;
		}

		return $nowMinutes >= $startMinutes && $nowMinutes <= $endMinutes;
	}

	/**
	 * Earliest instant at or after `$from` that satisfies the configured time
	 * window. Used for interval schedules so `next_run` does not sit in the
	 * past for hours while a row is intentionally held closed.
	 *
	 * @param \Cake\I18n\DateTime $from
	 * @return \Cake\I18n\DateTime|null
	 */
	public function nextWindowOpenAt(DateTime $from): ?DateTime {
		if (!$this->hasWindowRestrictions()) {
			return $from;
		}

		$allowed = $this->allowedWindowDays();
		if (is_string($this->get('window_days_of_week')) && $this->get('window_days_of_week') !== '' && $allowed === null) {
			return null;
		}

		$intervals = $this->windowIntervalsByDay();
		if ($intervals === null) {
			return null;
		}

		for ($offsetDays = 0; $offsetDays <= 14; $offsetDays++) {
			$day = $offsetDays === 0 ? $from : $from->addDays($offsetDays);
			$dow = (int)$day->format('w');
			if ($allowed !== null && !in_array($dow, $allowed, true)) {
				continue;
			}

			$date = $day->format('Y-m-d');
			foreach ($intervals as [$startSeconds, $endSeconds]) {
				$candidate = new DateTime($date . ' 00:00:00');
				$candidate = $candidate->addSeconds($startSeconds);
				if ($candidate->getTimestamp() < $from->getTimestamp()) {
					if ($from->format('Y-m-d') !== $date) {
						continue;
					}
					if ($from->getTimestamp() > (new DateTime($date . ' 00:00:00'))->addSeconds($endSeconds)->getTimestamp()) {
						continue;
					}

					return $from;
				}

				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Convert a time-of-day stored value (string `HH:MM:SS` or `HH:MM`,
	 * or a Time/DateTime instance) into minutes-from-midnight.
	 *
	 * @param mixed $time
	 * @return int|null
	 */
	protected static function timeToMinutes(mixed $time): ?int {
		if (is_object($time) && method_exists($time, 'format')) {
			return ((int)$time->format('G')) * 60 + (int)$time->format('i');
		}
		if (is_string($time) && preg_match('/^(\d{1,2}):(\d{2})/', $time, $m) === 1) {
			$hours = (int)$m[1];
			$minutes = (int)$m[2];
			if ($hours > 23 || $minutes > 59) {
				return null;
			}

			return $hours * 60 + $minutes;
		}

		return null;
	}

	/**
	 * @return \DateInterval|null
	 */
	public function calculateNextInterval(): ?DateInterval {
		if (substr($this->frequency, 0, 1) === '+') {
			// PHP 8.2 returns false on parse failure, 8.3+ throws. The @var pins the
			// union so phpstan accepts the instanceof check on both 8.2 and 8.3+ stubs.
			/** @var \DateInterval|false $interval */
			$interval = @DateInterval::createFromDateString(substr($this->frequency, 1));

			return $interval instanceof DateInterval ? $interval : null;
		}
		if (substr($this->frequency, 0, 1) === 'P') {
			return new DateInterval($this->frequency);
		}

		return null;
	}

	/**
	 * @throws \Exception
	 * @return \Cake\I18n\DateTime|null
	 */
	public function calculateNextRun(): ?DateTime {
		$lastRun = $this->last_run;
		$interval = $this->calculateNextInterval();

		if ($interval) {
			// Interval-based: first run is "now", subsequent runs are last_run + interval.
			$nextRun = $lastRun === null ? new DateTime() : $lastRun->add($interval);

			return $this->nextWindowOpenAt($nextRun);
		}

		// Cron expression: always honor the schedule, even on the first run.
		// Catch the specific exception types declared by the cron-expression library
		// (constructor throws InvalidArgumentException; getNextRunDate throws
		// RuntimeException) so phpstan does not flag a broader catch as "dead".
		try {
			$cron = new CronExpression(static::normalizeCronExpression($this->frequency));
			$dateTime = $cron->getNextRunDate();
		} catch (InvalidArgumentException | RuntimeException $e) {
			return null;
		}

		$nextRun = new DateTime($dateTime);
		if (!$this->hasWindowRestrictions()) {
			return $nextRun;
		}
		if (is_string($this->get('window_days_of_week')) && $this->get('window_days_of_week') !== '' && $this->allowedWindowDays() === null) {
			return null;
		}
		if ($this->windowIntervalsByDay() === null) {
			return null;
		}

		for ($attempt = 0; $attempt < 4000; $attempt++) {
			if ($this->isWithinWindow($nextRun)) {
				return $nextRun;
			}

			try {
				$nextRun = new DateTime($cron->getNextRunDate($nextRun->toDateTimeString()));
			} catch (InvalidArgumentException | RuntimeException) {
				return null;
			}
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public function hasWindowRestrictions(): bool {
		return $this->get('window_start_time') !== null
			|| $this->get('window_end_time') !== null
			|| ($this->get('window_days_of_week') !== null && $this->get('window_days_of_week') !== '');
	}

	/**
	 * @return array<int>|null
	 */
	protected function allowedWindowDays(): ?array {
		$daysCsv = $this->get('window_days_of_week');
		if (!is_string($daysCsv) || $daysCsv === '') {
			return null;
		}

		$parts = array_map('trim', explode(',', $daysCsv));
		$allowed = [];
		foreach ($parts as $part) {
			if ($part === '' || !ctype_digit($part)) {
				return null;
			}
			$day = (int)$part;
			if ($day < 0 || $day > 6) {
				return null;
			}
			$allowed[$day] = $day;
		}

		if (count($allowed) === 0) {
			return null;
		}

		sort($allowed);

		return $allowed;
	}

	/**
	 * @return array<array{0:int, 1:int}> |null
	 */
	protected function windowIntervalsByDay(): ?array {
		$start = $this->get('window_start_time');
		$end = $this->get('window_end_time');
		if ($start === null && $end === null) {
			return [[0, 86399]];
		}

		$startMinutes = $start !== null ? static::timeToMinutes($start) : null;
		$endMinutes = $end !== null ? static::timeToMinutes($end) : null;
		if (($start !== null && $startMinutes === null) || ($end !== null && $endMinutes === null)) {
			return null;
		}

		$startSeconds = $startMinutes !== null ? $startMinutes * 60 : null;
		$endSeconds = $endMinutes !== null ? ($endMinutes * 60) + 59 : null;
		if ($startSeconds !== null && $endSeconds === null) {
			return [[$startSeconds, 86399]];
		}
		if ($startSeconds === null && $endSeconds !== null) {
			return [[0, $endSeconds]];
		}
		if ($startSeconds === null || $endSeconds === null) {
			return null;
		}
		if ($endSeconds < $startSeconds) {
			return [
				[0, $endSeconds],
				[$startSeconds, 86399],
			];
		}

		return [[$startSeconds, $endSeconds]];
	}

	/**
	 * Typical seconds between scheduled runs, for comparing against the
	 * actual run duration. For irregular schedules (e.g. "0 9,17 * * *"
	 * fires at 09:00 then 17:00) this returns the gap to the FOLLOWING run
	 * after the next one — good enough as a yardstick for "is this job
	 * overrunning its window".
	 *
	 * @return int|null Interval in seconds, or null if the frequency cannot
	 *                  be parsed.
	 */
	public function calculateIntervalSeconds(): ?int {
		$interval = $this->calculateNextInterval();
		if ($interval) {
			$now = new DateTimeImmutable();

			return $now->add($interval)->getTimestamp() - $now->getTimestamp();
		}

		try {
			$cron = new CronExpression(static::normalizeCronExpression($this->frequency));
			$next1 = $cron->getNextRunDate('now', 0);
			$next2 = $cron->getNextRunDate('now', 1);
		} catch (InvalidArgumentException | RuntimeException) {
			return null;
		}

		return max(0, $next2->getTimestamp() - $next1->getTimestamp());
	}

	/**
	 * Normalize cron expression shortcuts (e.g. @minutely) into standard
	 * five-field cron format the upstream CronExpression library accepts.
	 *
	 * @param string $expression
	 * @return string
	 */
	public static function normalizeCronExpression(string $expression): string {
		if ($expression === '@minutely') {
			return '* * * * *';
		}

		return $expression;
	}

	/**
	 * @throws \Exception
	 * @return bool
	 */
	public function isCronExpression(): bool {
		$i = $this->calculateNextInterval();

		return $i === null;
	}

	/**
	 * Human-readable description of the frequency, suitable for tooltips
	 * or muted helper text. Cron expressions are described via the
	 * panlatent/cron-expression-descriptor library when available; interval-
	 * style frequencies (e.g. "+5 minutes", "PT5M") are described as
	 * "Every X" using the same DateInterval the next-run calc uses.
	 *
	 * @return string|null Description, or null if the frequency cannot be
	 *                     parsed or no descriptor library is available.
	 */
	public function getFrequencyDescription(): ?string {
		$interval = $this->calculateNextInterval();
		if ($interval) {
			$now = new DateTimeImmutable();
			$seconds = $now->add($interval)->getTimestamp() - $now->getTimestamp();
			if ($seconds <= 0) {
				return null;
			}
			if ($seconds % 86400 === 0) {
				$d = intdiv($seconds, 86400);

				return $d === 1 ? __d('queue_scheduler', 'Every day') : __d('queue_scheduler', 'Every {0} days', $d);
			}
			if ($seconds % 3600 === 0) {
				$h = intdiv($seconds, 3600);

				return $h === 1 ? __d('queue_scheduler', 'Every hour') : __d('queue_scheduler', 'Every {0} hours', $h);
			}
			if ($seconds % 60 === 0) {
				$m = intdiv($seconds, 60);

				return $m === 1 ? __d('queue_scheduler', 'Every minute') : __d('queue_scheduler', 'Every {0} minutes', $m);
			}

			return __d('queue_scheduler', 'Every {0} seconds', $seconds);
		}

		if (!class_exists(ExpressionDescriptor::class)) {
			return null;
		}

		try {
			$expression = (new CronExpression(static::normalizeCronExpression($this->frequency)))->getExpression();
		} catch (InvalidArgumentException) {
			return null;
		}
		if ($expression === null) {
			return null;
		}

		$descriptor = new ExpressionDescriptor($expression, Locale::getDefault(), true);
		$description = $descriptor->getDescription();

		return $description !== '' ? $description : null;
	}

	/**
	 * @return array<string, string>
	 */
	public static function shortcuts(): array {
		return array_combine(static::$shortcuts, static::$shortcuts);
	}

	/**
	 * @see \QueueScheduler\Model\Entity\SchedulerRow::$job_task
	 * @return string|null
	 */
	protected function _getJobTask(): ?string {
		if ($this->type === static::TYPE_QUEUE_TASK) {
			return Config::taskName($this->content);
		}
		if ($this->type === static::TYPE_CAKE_COMMAND) {
			return 'QueueScheduler.CommandExecute';
		}

		return 'Queue.Execute';
	}

	/**
	 * @see \QueueScheduler\Model\Entity\SchedulerRow::$job_data
	 * @return array
	 */
	protected function _getJobData(): array {
		$param = [];
		if ($this->param) {
			$decoded = json_decode($this->param, true, JSON_THROW_ON_ERROR);
			// Validation rejects scalar/null payloads at save time, but a row
			// inserted directly via SQL or through a marshalling path that
			// bypasses validation could still reach this method. Falling
			// through to QueuedJobsTable::createJob() with a non-array would
			// throw TypeError; default to [] instead so the dispatch is
			// well-formed and the failure surfaces clearly downstream.
			if (is_array($decoded)) {
				$param = $decoded;
			}
		}

		if ($this->type === static::TYPE_QUEUE_TASK) {
			return $param;
		}
		if ($this->type === static::TYPE_SHELL_COMMAND) {
			// Split on whitespace so the executable lands in `command` and each
			// argument lands as its own entry in `params`. ExecuteTask matches
			// `command` against `Queue.executeAllowedCommands` verbatim and
			// `escapeshellarg()`s each `params` entry individually, so the
			// allow-list can gate by executable (e.g. `bin/cake`) instead of
			// requiring an entry per argument permutation. Quote-aware
			// tokenization is intentionally not done — admins who need a
			// composite shell line should call a wrapper script.
			$tokens = preg_split('/\s+/', trim($this->content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
			$command = (string)array_shift($tokens);

			return ['command' => $command, 'params' => $tokens];
		}
		if ($this->type === static::TYPE_CAKE_COMMAND) {
			return ['class' => $this->content, 'args' => $param];
		}

		return [];
	}

	/**
	 * Always return an array so SchedulerRowsTable::run() can merge into it
	 * without guarding. The column uses Cake's `json` type, so values from the
	 * DB or marshalled input arrive here as arrays, but a direct
	 * $entity->set('job_config', '...json string...') is also tolerated.
	 *
	 * @see \QueueScheduler\Model\Entity\SchedulerRow::$job_config
	 * @param mixed $value Decoded array, raw JSON string, or null.
	 * @return array<string, mixed>
	 */
	protected function _getJobConfig(mixed $value): array {
		if (is_array($value)) {
			return $value;
		}
		if (is_string($value) && $value !== '') {
			$decoded = json_decode($value, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}

		return [];
	}

	/**
	 * @see \QueueScheduler\Model\Entity\SchedulerRow::$job_reference
	 * @return string
	 */
	protected function _getJobReference(): string {
		return 'queue-scheduler-' . $this->id;
	}

}
