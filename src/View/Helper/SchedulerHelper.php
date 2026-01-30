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

	/**
	 * Renders datalist elements for content field autocomplete.
	 *
	 * @return string
	 */
	public function contentDatalists(): string {
		$html = '<datalist id="content-commands">';
		foreach ($this->availableCommands() as $name => $command) {
			$html .= '<option value="' . h($command) . '" label="' . h($name) . '">';
		}
		$html .= '</datalist>';

		$html .= '<datalist id="content-queue-tasks">';
		foreach ($this->availableQueueTasks() as $name => $queueTask) {
			$html .= '<option value="' . h($queueTask) . '" label="' . h($name) . '">';
		}
		$html .= '</datalist>';

		return $html;
	}

}
