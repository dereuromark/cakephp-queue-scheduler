<?php declare(strict_types=1);

namespace QueueScheduler\Model\Entity;

use Cake\ORM\Entity;

/**
 * One historical dispatch of a SchedulerRow. Captures the queued-job
 * id at dispatch time + the resolved outcome (status, duration,
 * failure message) so the admin "did my nightly export run last week"
 * view survives even after the underlying queued_jobs row is cleaned.
 *
 * @property int $id
 * @property int $scheduler_row_id
 * @property int|null $queued_job_id
 * @property string $status One of: queued, completed, failed, aborted.
 * @property \Cake\I18n\DateTime $dispatched_at
 * @property \Cake\I18n\DateTime|null $completed_at
 * @property int|null $duration_ms
 * @property string|null $failure_message
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \QueueScheduler\Model\Entity\SchedulerRow|null $scheduler_row
 */
class SchedulerRun extends Entity {

	/**
	 * @var string
	 */
	public const STATUS_QUEUED = 'queued';

	/**
	 * @var string
	 */
	public const STATUS_COMPLETED = 'completed';

	/**
	 * @var string
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * @var string
	 */
	public const STATUS_ABORTED = 'aborted';

	/**
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'scheduler_row_id' => true,
		'queued_job_id' => true,
		'status' => true,
		'dispatched_at' => true,
		'completed_at' => true,
		'duration_ms' => true,
		'failure_message' => true,
	];

}
