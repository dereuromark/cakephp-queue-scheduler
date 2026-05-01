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
			static::TYPE_QUEUE_TASK => __('Queue Task'),
			static::TYPE_CAKE_COMMAND => __('Cake Command'),
			static::TYPE_SHELL_COMMAND => __('Shell Command (raw command execution)'),
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

		$dateTime = new DateTime();
		if ($nextRun) {
			return $nextRun->timestamp <= $dateTime->timestamp;
		}

		$nextInterval = $this->calculateNextInterval();
		if ($nextInterval) {
			if ($this->last_run === null) {
				return true;
			}

			return $this->last_run->add($nextInterval)->timestamp <= $dateTime->timestamp;
		}

		return (new CronExpression(static::normalizeCronExpression($this->frequency)))->isDue($dateTime->toDateTimeString());
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
			return $lastRun === null ? new DateTime() : $lastRun->add($interval);
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

		return new DateTime($dateTime);
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
			$param = json_decode($this->param, true, JSON_THROW_ON_ERROR);
		}

		if ($this->type === static::TYPE_QUEUE_TASK) {
			return $param;
		}
		if ($this->type === static::TYPE_SHELL_COMMAND) {
			return ['command' => $this->content];
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
