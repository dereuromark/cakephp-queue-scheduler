<?php
declare(strict_types=1);

namespace QueueScheduler\Model\Entity;

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
 * @property bool $allow_concurrent
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class Row extends Entity {

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
		'name' => true,
		'type' => true,
		'content' => true,
		'frequency' => true,
		'last_run' => true,
		'allow_concurrent' => true,
		'created' => true,
		'modified' => true,
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

}
