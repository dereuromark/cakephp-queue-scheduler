<?php declare(strict_types=1);

namespace QueueScheduler\View\Helper;

use Cake\View\Helper;
use Queue\Model\Entity\QueuedJob;
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

	/**
	 * Format a duration in seconds as a compact human label:
	 * "<1s" / "12s" / "5m 30s" / "2h 15m". Output may contain "<", so
	 * callers should pass it through h() when rendering as text.
	 *
	 * @param int $seconds
	 *
	 * @return string
	 */
	public function duration(int $seconds): string {
		if ($seconds < 1) {
			return '<1s';
		}
		if ($seconds < 60) {
			return $seconds . 's';
		}
		if ($seconds < 3600) {
			$m = intdiv($seconds, 60);
			$s = $seconds % 60;

			return $s ? "{$m}m {$s}s" : "{$m}m";
		}
		$h = intdiv($seconds, 3600);
		$m = intdiv($seconds % 3600, 60);

		return $m ? "{$h}h {$m}m" : "{$h}h";
	}

	/**
	 * Bootstrap utility class for a duration value, colored by how close
	 * it is to the schedule interval. Returns text-warning at >= 80% and
	 * text-danger at >= 100% (i.e. the run is overrunning its window).
	 * Returns text-muted when the interval is unknown or zero.
	 *
	 * @param int $seconds Duration of the run.
	 * @param int|null $intervalSec Schedule interval in seconds.
	 *
	 * @return string
	 */
	public function durationClass(int $seconds, ?int $intervalSec): string {
		if ($intervalSec === null || $intervalSec <= 0) {
			return 'text-muted';
		}
		$ratio = $seconds / $intervalSec;
		if ($ratio >= 1.0) {
			return 'text-danger fw-semibold';
		}
		if ($ratio >= 0.8) {
			return 'text-warning fw-semibold';
		}

		return 'text-muted';
	}

	/**
	 * Inline icon HTML summarizing a run's pass/fail status. Returns an
	 * empty string when the job hasn't completed or wasn't loaded, so the
	 * caller can echo unconditionally.
	 *
	 * @param \Queue\Model\Entity\QueuedJob|null $job
	 *
	 * @return string
	 */
	public function runStatusIcon(?QueuedJob $job): string {
		if ($job === null) {
			return '';
		}
		if ($job->failure_message) {
			$title = (string)__('Last run failed');

			return '<i class="fas fa-times-circle text-danger me-1" title="' . h($title)
				. '" aria-label="' . h($title) . '"></i>';
		}
		if ($job->completed) {
			$title = (string)__('Last run succeeded');

			return '<i class="fas fa-check-circle text-success me-1" title="' . h($title)
				. '" aria-label="' . h($title) . '"></i>';
		}

		return '';
	}

}
