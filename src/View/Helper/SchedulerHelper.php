<?php declare(strict_types=1);

namespace QueueScheduler\View\Helper;

use Cake\View\Helper;
use Queue\Queue\TaskFinder;
use QueueScheduler\Utility\CommandFinder;

/**
 * Scheduler helper
 */
class SchedulerHelper extends Helper {

	/**
	 * @return array<string, string>
	 */
	public function availableCommands(): array {
		return (new CommandFinder())->all();
	}

	/**
	 * @return array<string, string>
	 */
	public function availableQueueTasks(): array {
		return (new TaskFinder())->all();
	}

}
