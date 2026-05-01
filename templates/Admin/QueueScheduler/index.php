<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\QueueScheduler\Model\Entity\SchedulerRow> $schedulerRows
 * @var array<\Queue\Model\Entity\QueuedJob> $runningJobs
 * @var array{lastTick: int|null, healthy: bool, ageSeconds: int|null, thresholdSeconds: int} $schedulerStatus
 */

if ($schedulerStatus['lastTick'] !== null) {
	$lastTickDt = (new \Cake\I18n\DateTime())->setTimestamp($schedulerStatus['lastTick']);
	$relTime = method_exists($this->Time, 'relLengthOfTime')
		? $this->Time->relLengthOfTime($lastTickDt)
		: $this->Time->timeAgoInWords($lastTickDt);
} else {
	$lastTickDt = null;
	$relTime = null;
}
?>
<div class="scheduler-dashboard">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-calendar-alt me-2"></i><?= __('Queue Scheduler') ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-plus me-1"></i>' . __('New Schedule'),
				['controller' => 'SchedulerRows', 'action' => 'add'],
				['class' => 'btn btn-primary', 'escapeTitle' => false],
			) ?>
		</div>
	</div>

	<p class="text-muted mb-2"><?= __('Addon to run commands and queue tasks as crontab like database driven schedule.') ?></p>

	<div class="mb-4">
		<?php if ($schedulerStatus['lastTick'] === null) { ?>
			<span
				class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle"
				title="<?= h(__('Cron has not invoked the scheduler yet, or the cache config is not shared between web and CLI.')) ?>"
			>
				<i class="fas fa-pause-circle me-1"></i><?= __('Scheduler: never run') ?>
			</span>
		<?php } elseif ($schedulerStatus['healthy']) { ?>
			<span
				class="badge bg-success-subtle text-success-emphasis border border-success-subtle"
				title="<?= h(__('Last tick {0}', $lastTickDt ? $this->Time->nice($lastTickDt) : '')) ?>"
			>
				<i class="fas fa-check-circle me-1"></i><?= __('Scheduler healthy') ?>
				<span class="text-muted ms-1">&middot; <?= h($relTime) ?></span>
			</span>
		<?php } else { ?>
			<span
				class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"
				title="<?= h(__('Last tick was {0} ago; threshold is {1}. Check that cron is invoking `bin/cake scheduler run`.', $this->Scheduler->duration((int)$schedulerStatus['ageSeconds']), $this->Scheduler->duration($schedulerStatus['thresholdSeconds']))) ?>"
			>
				<i class="fas fa-exclamation-triangle me-1"></i><?= __('Scheduler stale') ?>
				<span class="text-muted ms-1">&middot; <?= h($relTime) ?></span>
			</span>
		<?php } ?>
	</div>

	<div class="card">
		<div class="card-header">
			<i class="fas fa-clock me-2"></i><?= __('Currently Scheduled') ?>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover scheduler-table mb-0">
					<thead>
						<tr>
							<th><?= __('Name') ?></th>
							<th><?= __('Frequency') ?></th>
							<th><?= __('Status') ?></th>
							<th class="actions text-end"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $rowCount = 0; ?>
						<?php foreach ($schedulerRows as $schedulerRow) { ?>
							<?php
							$rowCount++;
							$queuedJob = $runningJobs[$schedulerRow->job_reference] ?? null;
							$frequencyDescription = $schedulerRow->getFrequencyDescription();
							?>
							<tr>
								<td>
									<?= $this->Html->link(
										h($schedulerRow->name),
										['controller' => 'SchedulerRows', 'action' => 'view', $schedulerRow->id],
										['class' => 'fw-semibold'],
									) ?>
									<div>
										<small class="text-muted"><?= $schedulerRow::types($schedulerRow->type) ?></small>
									</div>
								</td>
								<td>
									<code<?= $frequencyDescription ? ' title="' . h($frequencyDescription) . '"' : '' ?>><?= h($schedulerRow->frequency) ?></code>
									<?php if ($frequencyDescription) { ?>
										<div><small class="text-muted"><?= h($frequencyDescription) ?></small></div>
									<?php } ?>
								</td>
								<td>
									<?php if ($queuedJob) { ?>
										<div class="alert alert-job-running mb-2 p-2">
											<strong>
												<?= $this->Html->link(
													h($queuedJob->status) ?: __('Queued'),
													['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id],
												) ?>
											</strong>
											<?php if (!$queuedJob->completed && $queuedJob->fetched) { ?>
												<?php if (!$queuedJob->failure_message) { ?>
													<div class="mt-1">
														<?= $this->QueueProgress->progress($queuedJob) ?>
														<br>
														<?php
														$textProgressBar = $this->QueueProgress->progressBar($queuedJob, 18);
														echo $this->QueueProgress->htmlProgressBar($queuedJob, $textProgressBar);
														?>
													</div>
												<?php } else { ?>
													<div class="mt-1">
														<i class="text-danger"><?= $this->Queue->failureStatus($queuedJob) ?></i>
													</div>
												<?php } ?>
											<?php } ?>
										</div>
									<?php } ?>
									<?php if ($schedulerRow->last_run) { ?>
										<?php $lastJob = $schedulerRow->last_queued_job; ?>
										<div class="small">
											<?= $this->Scheduler->runStatusIcon($lastJob) ?>
											<span class="text-muted"><?= __('Last Run') ?>:</span>
											<?php if ($schedulerRow->last_queued_job_id) { ?>
												<?= $this->Html->link(
													$this->Time->nice($schedulerRow->last_run),
													['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $schedulerRow->last_queued_job_id],
												) ?>
											<?php } else { ?>
												<?= $this->Time->nice($schedulerRow->last_run) ?>
											<?php } ?>
											<?php if ($lastJob && $lastJob->fetched && $lastJob->completed) { ?>
												<?php $durationSec = max(0, $lastJob->completed->getTimestamp() - $lastJob->fetched->getTimestamp()); ?>
												<span class="<?= h($this->Scheduler->durationClass($durationSec, $schedulerRow->calculateIntervalSeconds())) ?>">(<?= h($this->Scheduler->duration($durationSec)) ?>)</span>
											<?php } ?>
										</div>
									<?php } ?>
									<?php
									$nextRun = $schedulerRow->next_run ?: $schedulerRow->calculateNextRun();
									$nextRunOverdue = $nextRun && $nextRun->getTimestamp() < time();
									?>
									<?php if ($nextRun) { ?>
										<div class="small">
											<span class="text-muted"><?= __('Next Run') ?>: <?= $this->Time->nice($nextRun) ?></span>
											<span class="<?= $nextRunOverdue ? 'text-danger fw-semibold' : 'text-muted' ?>">(<?= h($this->Time->timeAgoInWords($nextRun)) ?>)</span>
										</div>
									<?php } ?>
								</td>
								<td class="actions text-end">
									<?php if (!$queuedJob) { ?>
										<?= $this->Form->postButton(
											'<i class="fas fa-play-circle" aria-hidden="true"></i>',
											['controller' => 'SchedulerRows', 'action' => 'run', $schedulerRow->id],
											[
												'escapeTitle' => false,
												'class' => 'btn btn-sm btn-success me-1',
												'title' => __('Run manually now'),
												'aria-label' => __('Run manually now'),
												'form' => [
													'class' => 'd-inline js-scheduler-run-form',
													'data-confirm-message' => __('Sure to run it now?'),
												],
											],
										) ?>
									<?php } ?>
									<?= $this->Form->postButton(
										'<i class="fas fa-pause-circle" aria-hidden="true"></i>',
										['controller' => 'SchedulerRows', 'action' => 'edit', $schedulerRow->id],
										[
											'data' => ['enabled' => 0],
											'escapeTitle' => false,
											'class' => 'btn btn-sm btn-warning',
											'title' => __('Disable'),
											'aria-label' => __('Disable'),
											'form' => [
												'class' => 'd-inline',
												'data-confirm-message' => __('Sure to disable?'),
											],
										],
									) ?>
								</td>
							</tr>
						<?php } ?>
						<?php if ($rowCount === 0) { ?>
							<tr>
								<td colspan="4" class="text-center text-muted py-4">
									<i class="fas fa-calendar-plus mb-2 d-block fs-4" aria-hidden="true"></i>
									<?= __('No schedules yet.') ?>
									<?= $this->Html->link(
										__('Create your first schedule'),
										['controller' => 'SchedulerRows', 'action' => 'add'],
									) ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<?php
	$newCount = 0;
	foreach ($schedulerRows as $row) {
		if ($row->last_run === null) {
			$newCount++;
		}
	}
	?>
	<div class="mt-3">
		<?= $this->Html->link(
			'<i class="fas fa-list me-1"></i>' . __('All Schedules'),
			['controller' => 'SchedulerRows', 'action' => 'index'],
			['class' => 'btn btn-secondary me-2', 'escapeTitle' => false],
		) ?>
		<?php if ($newCount >= 2) { ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-play-circle me-1"></i>' . __('Run New ({0})', $newCount),
				['controller' => 'SchedulerRows', 'action' => 'runAllNew'],
				[
					'escapeTitle' => false,
					'class' => 'btn btn-success me-2',
					'title' => __('Queue all enabled schedules that have never run yet'),
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __('Queue {0} new schedule(s) now?', $newCount),
					],
				],
			) ?>
		<?php } ?>
		<?= $this->Form->postButton(
			'<i class="fas fa-pause-circle me-1"></i>' . __('Disable All'),
			['controller' => 'SchedulerRows', 'action' => 'disableAll'],
			[
				'escapeTitle' => false,
				'class' => 'btn btn-warning',
				'form' => [
					'class' => 'd-inline',
					'data-confirm-message' => __('Sure to disable all?'),
				],
			],
		) ?>
	</div>
</div>
<script>
(function () {
	var spinnerHtml = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form.classList || !form.classList.contains('js-scheduler-run-form')) {
			return;
		}
		e.preventDefault();
		e.stopImmediatePropagation();
		var message = form.getAttribute('data-confirm-message') || 'Are you sure?';
		if (!window.confirm(message)) {
			return;
		}
		var button = form.querySelector('button');
		var originalHtml = button.innerHTML;
		button.disabled = true;
		button.innerHTML = spinnerHtml;
		fetch(form.action, {
			method: 'POST',
			body: new FormData(form),
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			},
			credentials: 'same-origin'
		}).then(function (r) {
			return r.ok ? r.json() : Promise.reject(new Error('http_' + r.status));
		}).then(function (data) {
			if (data && data.success) {
				// Brief delay so the worker has a chance to pick up the job
				// and the reloaded page can show running-state UI.
				setTimeout(function () { window.location.reload(); }, 800);
			} else {
				button.disabled = false;
				button.innerHTML = originalHtml;
				window.alert((data && data.message) || 'Failed to queue job.');
			}
		}).catch(function () {
			button.disabled = false;
			button.innerHTML = originalHtml;
			window.alert('Network error while queueing job.');
		});
	}, true);
})();
</script>
