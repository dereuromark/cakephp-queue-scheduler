<?php declare(strict_types=1);

namespace QueueScheduler\View\Helper;

use Cake\View\Helper;
use Queue\Queue\TaskFinder;
use QueueScheduler\Model\Entity\SchedulerRow;
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

	/**
	 * Renders datalist element for frequency field autocomplete.
	 *
	 * @return string
	 */
	public function frequencyDatalist(): string {
		$html = '<datalist id="frequency-suggestions">';
		foreach (SchedulerRow::shortcuts() as $shortcut) {
			$html .= '<option value="' . h($shortcut) . '">';
		}

		$commonCron = [
			'*/5 * * * *' => 'Every 5 minutes',
			'*/15 * * * *' => 'Every 15 minutes',
			'*/30 * * * *' => 'Every 30 minutes',
			'0 */2 * * *' => 'Every 2 hours',
			'0 */6 * * *' => 'Every 6 hours',
			'0 0 * * 1-5' => 'Weekdays at midnight',
		];
		foreach ($commonCron as $expression => $label) {
			$html .= '<option value="' . h($expression) . '" label="' . h($label) . '">';
		}
		$html .= '</datalist>';

		return $html;
	}

}
