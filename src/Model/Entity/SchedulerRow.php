<?php
declare(strict_types=1);

namespace QueueScheduler\Model\Entity;

use Cake\I18n\FrozenTime;
use Cron\CronExpression;
use DateInterval;
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
 * @property string $frequency
 * @property \Cake\I18n\FrozenTime|null $last_run
 * @property \Cake\I18n\FrozenTime|null $next_run
 * @property bool $allow_concurrent
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property bool $enabled
 * @property-read string|null $job_task
 * @property-read array $job_data
 * @property-read array $job_config
 * @property-read string $job_reference
 */
class SchedulerRow extends Entity {

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array<string, bool>
	 */
	protected $_accessible = [
		'*' => true,
		'id' => false,
	];

	/**
	 * @param array<int>|int|null $value
	 *
	 * @return array<string, string>|string
	 */
	public static function types($value = null) {
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

		$dateTime = new FrozenTime();
		if ($nextRun) {
			return $nextRun->timestamp < $dateTime->timestamp;
		}

		$nextInterval = $this->calculateNextInterval();
		if ($nextInterval) {
			if ($this->last_run === null) {
				return true;
			}

			return $this->last_run->add($nextInterval)->timestamp < $dateTime->timestamp;
		}

		return (new CronExpression($this->frequency))->isDue($dateTime->toDateTimeString());
	}

	/**
	 * @throws \Exception
	 * @return \DateInterval|null
	 */
	public function calculateNextInterval(): ?DateInterval {
		$i = null;
		if (substr($this->frequency, 0, 1) === '+') {
			$i = DateInterval::createFromDateString(substr($this->frequency, 1));
			if ($i === false) {
				throw new RuntimeException('Cannot create interval from date string `' . $this->frequency . '`');
			}
		} elseif (substr($this->frequency, 0, 1) === 'P') {
			$i = new DateInterval($this->frequency);
		}

		return $i;
	}

	/**
	 * @throws \Exception
	 * @return \Cake\I18n\FrozenTime|null
	 */
	public function calculateNextRun() {
		$dateTime = $this->last_run;
		if ($dateTime === null) {
			return new FrozenTime();
		}

		$i = $this->calculateNextInterval();
		if ($i) {
			return $dateTime->add($i);
		}

		return null;
	}

	/**
	 *@see \QueueScheduler\Model\Entity\SchedulerRow::$job_task
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
	 *@see \QueueScheduler\Model\Entity\SchedulerRow::$job_data
	 * @return array
	 */
	protected function _getJobData(): array {
		if ($this->type === static::TYPE_SHELL_COMMAND) {
			return ['command' => $this->content];
		}
		if ($this->type === static::TYPE_CAKE_COMMAND) {
			return ['class' => $this->content];
		}

		return [];
	}

	/**
	 *@see \QueueScheduler\Model\Entity\SchedulerRow::$job_config
	 * @return array
	 */
	protected function _getJobConfig(): array {
		return [];
	}

	/**
	 *@see \QueueScheduler\Model\Entity\SchedulerRow::$job_reference
	 * @return string
	 */
	protected function _getJobReference(): string {
		return 'queue-scheduler-' . $this->id;
	}

}
