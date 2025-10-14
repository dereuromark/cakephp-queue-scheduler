<?php declare(strict_types=1);

namespace QueueScheduler\Model\Entity;

use Cake\ORM\Entity;

/**
 * CommandLog Entity
 *
 * @property int $id
 * @property string $command
 * @property string|null $arguments
 * @property string|null $stdout
 * @property string|null $stderr
 * @property int|null $job_id
 * @property float|null $execution_time
 * @property bool $success
 * @property string|null $error_message
 * @property string|null $metadata
 * @property \Cake\I18n\DateTime $executed_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class CommandLog extends Entity {

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'command' => true,
		'arguments' => true,
		'stdout' => true,
		'stderr' => true,
		'job_id' => true,
		'execution_time' => true,
		'success' => true,
		'error_message' => true,
		'metadata' => true,
		'executed_at' => true,
		'created' => true,
		'modified' => true,
	];

	/**
	 * Get decoded metadata
	 *
	 * @return array|null
	 */
	public function getDecodedMetadata(): ?array {
		if (!$this->metadata) {
			return null;
		}

		return json_decode($this->metadata, true);
	}

	/**
	 * Get decoded arguments
	 *
	 * @return array|null
	 */
	public function getDecodedArguments(): ?array {
		if (!$this->arguments) {
			return null;
		}

		return json_decode($this->arguments, true);
	}

	/**
	 * Check if log has errors
	 *
	 * @param bool $includeNested Whether to include nested entity errors (unused in this context)
	 *
	 * @return bool
	 */
	public function hasErrors(bool $includeNested = true): bool {
		return !$this->success || !empty($this->stderr) || !empty($this->error_message);
	}

	/**
	 * Get a summary of the log
	 *
	 * @param int $maxLength Maximum length for stdout/stderr
	 *
	 * @return array
	 */
	public function getSummary(int $maxLength = 500): array {
		return [
			'command' => $this->command,
			'success' => $this->success,
			'execution_time' => $this->execution_time,
			'executed_at' => $this->executed_at->format('Y-m-d H:i:s'),
			'stdout_preview' => $this->stdout ? substr($this->stdout, 0, $maxLength) : null,
			'stderr_preview' => $this->stderr ? substr($this->stderr, 0, $maxLength) : null,
			'error_message' => $this->error_message,
			'has_more_output' => (strlen($this->stdout ?? '') > $maxLength) || (strlen($this->stderr ?? '') > $maxLength),
		];
	}

}
