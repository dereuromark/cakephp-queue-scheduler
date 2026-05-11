<?php

use Cron\CronExpression;
use QueueScheduler\Model\Entity\SchedulerRow;

/**
 * @var \App\View\AppView $this
 * @var \QueueScheduler\Model\Entity\SchedulerRow $row
 * @var array<string, mixed>|null $jobStats
 * @var array<\Queue\Model\Entity\QueuedJob> $recentJobs
 */

$intervalSec = $row->calculateIntervalSeconds();
$frequencyDescription = $row->getFrequencyDescription();
$isQueueTask = $row->type === SchedulerRow::TYPE_QUEUE_TASK;
$windowValue = static function (mixed $value): ?string {
	if ($value === null || $value === '') {
		return null;
	}
	if (is_object($value) && method_exists($value, 'format')) {
		return $value->format('H:i');
	}

	return is_string($value) ? substr($value, 0, 5) : null;
};
$windowStart = $windowValue($row->get('window_start_time'));
$windowEnd = $windowValue($row->get('window_end_time'));
$windowDays = $row->get('window_days_of_week');
?>
<div class="scheduler-rows-view">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-calendar-check me-2"></i><?= h($row->name) ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-edit me-1"></i>' . __d('queue_scheduler', 'Edit'),
				['action' => 'edit', $row->id],
				['class' => 'btn btn-primary me-2', 'escapeTitle' => false],
			) ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-play-circle me-1"></i>' . __d('queue_scheduler', 'Run Now'),
				['action' => 'run', $row->id],
				[
					'class' => 'btn btn-success me-2',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline js-scheduler-run-form',
						'data-confirm-message' => __d('queue_scheduler', 'Are you sure you want to run this now?'),
					],
				],
			) ?>
			<?php if ($isQueueTask) { ?>
				<button type="button" class="btn btn-outline-success me-2" data-bs-toggle="collapse" data-bs-target="#scheduler-run-override" aria-expanded="false" aria-controls="scheduler-run-override">
					<i class="fas fa-sliders-h me-1"></i><?= __d('queue_scheduler', 'Run with overrides…') ?>
				</button>
			<?php } ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-trash me-1"></i>' . __d('queue_scheduler', 'Delete'),
				['action' => 'delete', $row->id],
				[
					'class' => 'btn btn-danger',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __d('queue_scheduler', 'Are you sure you want to delete # {0}?', $row->id),
					],
				],
			) ?>
		</div>
	</div>

	<?php if ($isQueueTask) { ?>
		<div class="collapse mb-4" id="scheduler-run-override">
			<div class="card border-success">
				<div class="card-header bg-success-subtle">
					<i class="fas fa-sliders-h me-2"></i><?= __d('queue_scheduler', 'Ad-hoc trigger with overrides') ?>
				</div>
				<div class="card-body">
					<p class="text-muted small mb-3">
						<?= __d('queue_scheduler', 'This dispatches the job once with the values below, in addition to the normal schedule. {0}last_run{1} and {0}next_run{1} are NOT advanced, so the row keeps firing on its regular cadence. Each override dispatch is logged at info level. Leave a field blank to use the row\'s stored value.', '<code>', '</code>') ?>
					</p>
					<?= $this->Form->create(null, [
						'url' => ['action' => 'run', $row->id],
						'class' => 'js-scheduler-run-form',
						'data-confirm-message' => __d('queue_scheduler', 'Dispatch this job once with the overrides below?'),
					]) ?>
					<div class="row">
						<div class="col-md-6 mb-3">
							<?= $this->Form->control('override_param', [
								'type' => 'textarea',
								'label' => __d('queue_scheduler', 'Param override (JSON)'),
								'placeholder' => '{"tenant_id": 42}',
								'value' => $row->param,
								'rows' => 4,
								'class' => 'form-control font-monospace',
								'help' => __d('queue_scheduler', 'Sent as the queue job payload. Must be a valid JSON object.'),
							]) ?>
						</div>
						<div class="col-md-6 mb-3">
							<?= $this->Form->control('override_job_config', [
								'type' => 'textarea',
								'label' => __d('queue_scheduler', 'Job config override (JSON)'),
								'placeholder' => '{"priority": 1}',
								'value' => $row->job_config ? (string)json_encode($row->job_config) : '',
								'rows' => 4,
								'class' => 'form-control font-monospace',
								'help' => __d('queue_scheduler', 'Allowed keys: priority (1-10), group. Merged on top of the row\'s stored job_config.'),
							]) ?>
						</div>
					</div>
					<?= $this->Form->button(
						'<i class="fas fa-play me-1"></i>' . __d('queue_scheduler', 'Dispatch override now'),
						['class' => 'btn btn-success', 'escapeTitle' => false, 'type' => 'submit'],
					) ?>
					<?= $this->Form->end() ?>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="row">
		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-info-circle me-2"></i><?= __d('queue_scheduler', 'Schedule Details') ?>
				</div>
				<div class="card-body">
					<table class="table table-striped mb-0">
						<tr>
							<th class="scheduler-col-w-40"><?= __d('queue_scheduler', 'Type') ?></th>
							<td><?= $row::types($row->type) ?></td>
						</tr>
						<?php if ($row->param) { ?>
							<tr>
								<th><?= __d('queue_scheduler', 'Config') ?></th>
								<td><pre class="mb-0"><?= h(json_encode(json_decode($row->param, true), JSON_PRETTY_PRINT)) ?></pre></td>
							</tr>
						<?php } ?>
						<?php if ($row->job_config) { ?>
							<tr>
								<th><?= __d('queue_scheduler', 'Job Config') ?></th>
								<td><pre class="mb-0"><?= h(json_encode($row->job_config, JSON_PRETTY_PRINT)) ?></pre></td>
							</tr>
						<?php } ?>
						<tr>
							<th><?= __d('queue_scheduler', 'Frequency') ?></th>
							<td>
								<code<?= $frequencyDescription ? ' title="' . h($frequencyDescription) . '"' : '' ?>><?= h($row->frequency) ?></code>
								<?php if ($frequencyDescription) { ?>
									<div class="mt-1 text-muted"><?= h($frequencyDescription) ?></div>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<th><?= __d('queue_scheduler', 'Enabled') ?></th>
							<td>
								<?= $this->element('QueueScheduler.yes_no', ['value' => $row->enabled]) ?>
								<?= $row->enabled ? __d('queue_scheduler', 'Yes') : __d('queue_scheduler', 'No') ?>
								<?php if (!$row->enabled) { ?>
									<?= $this->Form->postButton(
										'<i class="fas fa-check me-1"></i>' . __d('queue_scheduler', 'Enable'),
										['action' => 'edit', $row->id],
										[
											'data' => ['enabled' => 1],
											'escapeTitle' => false,
											'class' => 'btn btn-sm btn-success ms-2',
											'form' => [
												'class' => 'd-inline',
												'data-confirm-message' => __d('queue_scheduler', 'Sure to enable?'),
											],
										],
									) ?>
								<?php } ?>
							</td>
						</tr>
							<tr>
								<th><?= __d('queue_scheduler', 'Allow Concurrent') ?></th>
								<td>
									<?= $this->element('QueueScheduler.yes_no', ['value' => $row->allow_concurrent]) ?>
									<?= $row->allow_concurrent ? __d('queue_scheduler', 'Yes') : __d('queue_scheduler', 'No') ?>
								</td>
							</tr>
							<tr>
								<th><?= __d('queue_scheduler', 'Dispatch Window') ?></th>
								<td>
									<?php if (!$row->hasWindowRestrictions()) { ?>
										<span class="text-muted"><?= __d('queue_scheduler', 'No extra restrictions') ?></span>
									<?php } else { ?>
										<div>
											<?= $windowStart !== null ? h($windowStart) : __d('queue_scheduler', 'no start') ?>
											-
											<?= $windowEnd !== null ? h($windowEnd) : __d('queue_scheduler', 'no end') ?>
										</div>
										<div class="small text-muted">
											<?= $windowDays ? __d('queue_scheduler', 'Days: {0}', $windowDays) : __d('queue_scheduler', 'Days: every day') ?>
										</div>
									<?php } ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
		</div>

		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-clock me-2"></i><?= __d('queue_scheduler', 'Timing Information') ?>
				</div>
				<div class="card-body">
					<table class="table table-striped mb-0">
						<tr>
							<th class="scheduler-col-w-40"><?= __d('queue_scheduler', 'Last Run') ?></th>
							<td>
								<?php $lastJob = $row->last_queued_job; ?>
								<?php if (!$row->last_run) { ?>
									<span class="text-muted"><?= __d('queue_scheduler', 'Never') ?></span>
								<?php } else { ?>
									<?= $this->Scheduler->runStatusIcon($lastJob) ?>
									<?php if ($row->last_queued_job_id) { ?>
										<?= $this->Html->link(
											$this->Time->nice($row->last_run),
											['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $row->last_queued_job_id],
										) ?>
									<?php } else { ?>
										<?= $this->Time->nice($row->last_run) ?>
									<?php } ?>
									<?php if ($lastJob && $lastJob->fetched && $lastJob->completed) { ?>
										<?php $durationSec = max(0, $lastJob->completed->getTimestamp() - $lastJob->fetched->getTimestamp()); ?>
										<span class="<?= h($this->Scheduler->durationClass($durationSec, $intervalSec)) ?>">(<?= h($this->Scheduler->duration($durationSec)) ?>)</span>
									<?php } ?>
									<?php if ($lastJob && $lastJob->failure_message) { ?>
										<div class="small text-danger mt-1">
											<?= h(mb_strimwidth((string)$lastJob->failure_message, 0, 200, '…')) ?>
										</div>
									<?php } ?>
								<?php } ?>
							</td>
						</tr>
						<?php
						$nextRun = $row->next_run ?: $row->calculateNextRun();
						$nextRunOverdue = $nextRun && $row->enabled && $nextRun->getTimestamp() < time();
						$intervalSec = $row->calculateIntervalSeconds();
						$grosslyOverdue = $nextRunOverdue
							&& $intervalSec !== null && $intervalSec > 0
							&& (time() - $nextRun->getTimestamp()) > 5 * $intervalSec;
						?>
						<?php if ($nextRun) { ?>
							<tr>
								<th><?= __d('queue_scheduler', 'Next Run') ?></th>
								<td>
									<?= $this->Time->nice($nextRun) ?>
									<?php if (!$row->enabled) { ?>
										<span class="badge bg-secondary ms-1"><?= __d('queue_scheduler', 'Disabled — won\'t run') ?></span>
									<?php } else { ?>
										<div class="small <?= $nextRunOverdue ? 'text-danger fw-semibold' : 'text-muted' ?>">
											<?= $grosslyOverdue ? __d('queue_scheduler', 'overdue') : h($this->Time->timeAgoInWords($nextRun)) ?>
										</div>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<th><?= __d('queue_scheduler', 'Created') ?></th>
							<td><?= $this->Time->nice($row->created) ?></td>
						</tr>
						<tr>
							<th><?= __d('queue_scheduler', 'Modified') ?></th>
							<td><?= $this->Time->nice($row->modified) ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="card mb-4">
		<div class="card-header">
			<i class="fas fa-code me-2"></i><?= __d('queue_scheduler', 'Content') ?>
		</div>
		<div class="card-body">
			<pre class="mb-0"><?= h($row->content) ?></pre>
		</div>
	</div>

	<?php if ($jobStats && $jobStats['total_runs']) { ?>
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-chart-bar me-2"></i><?= __d('queue_scheduler', 'Job Statistics') ?>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-3 mb-3 mb-md-0">
						<div class="text-center">
							<div class="h3 mb-0"><?= $jobStats['total_runs'] ?></div>
							<small class="text-muted"><?= __d('queue_scheduler', 'Total Runs') ?></small>
						</div>
					</div>
					<div class="col-md-3 mb-3 mb-md-0">
						<div class="text-center">
							<div class="h3 mb-0 text-success"><?= $jobStats['completed_runs'] ?: 0 ?></div>
							<small class="text-muted"><?= __d('queue_scheduler', 'Completed') ?></small>
						</div>
					</div>
					<div class="col-md-3 mb-3 mb-md-0">
						<div class="text-center">
							<div class="h3 mb-0 text-danger"><?= $jobStats['failed_runs'] ?: 0 ?></div>
							<small class="text-muted"><?= __d('queue_scheduler', 'Failed') ?></small>
						</div>
					</div>
					<?php if ($jobStats['avg_duration'] !== null) { ?>
						<div class="col-md-3">
							<div class="text-center">
								<div class="h3 mb-0"><?= $this->Number->precision($jobStats['avg_duration'], 1) ?>s</div>
								<small class="text-muted"><?= __d('queue_scheduler', 'Avg Duration') ?></small>
								<div class="small text-muted"><?= $jobStats['min_duration'] ?>s - <?= $jobStats['max_duration'] ?>s</div>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="card mb-4">
		<div class="card-header d-flex justify-content-between align-items-center">
			<span><i class="fas fa-history me-2"></i><?= __d('queue_scheduler', 'Recent Executions') ?></span>
			<?php if ($recentJobs) { ?>
				<?= $this->Html->link(
					__d('queue_scheduler', 'View All in Queue'),
					['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'index', '?' => ['search' => $row->job_reference]],
					['class' => 'btn btn-sm btn-outline-secondary'],
				) ?>
			<?php } ?>
		</div>
		<?php if (!$recentJobs) { ?>
			<div class="card-body text-center text-muted py-4">
				<?= __d('queue_scheduler', 'No runs recorded yet.') ?>
			</div>
		<?php } else { ?>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr>
								<th><?= __d('queue_scheduler', 'Created') ?></th>
								<th><?= __d('queue_scheduler', 'Status') ?></th>
								<th><?= __d('queue_scheduler', 'Duration') ?></th>
								<th><?= __d('queue_scheduler', 'Output') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($recentJobs as $job) { ?>
								<tr>
									<td>
										<?= $this->Html->link(
											$this->Time->nice($job->created),
											['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $job->id],
											['escapeTitle' => false],
										) ?>
									</td>
									<td>
										<?php if ($job->completed) { ?>
											<?php if ($job->failure_message) { ?>
												<span class="badge bg-danger"><?= __d('queue_scheduler', 'Failed') ?></span>
											<?php } else { ?>
												<span class="badge bg-success"><?= __d('queue_scheduler', 'Completed') ?></span>
											<?php } ?>
										<?php } elseif ($job->fetched) { ?>
											<span class="badge bg-info"><?= __d('queue_scheduler', 'Running') ?></span>
										<?php } else { ?>
											<span class="badge bg-secondary"><?= __d('queue_scheduler', 'Queued') ?></span>
										<?php } ?>
									</td>
									<td>
										<?php if ($job->fetched && $job->completed) { ?>
											<?php $jobDurationSec = (int)$job->fetched->diffInSeconds($job->completed); ?>
											<span class="<?= h($this->Scheduler->durationClass($jobDurationSec, $intervalSec)) ?>"><?= h($this->Scheduler->duration($jobDurationSec)) ?></span>
										<?php } elseif ($job->fetched) { ?>
											<span class="text-muted"><?= __d('queue_scheduler', 'In progress...') ?></span>
										<?php } else { ?>
											-
										<?php } ?>
									</td>
									<td>
										<?php if ($job->output) { ?>
											<details>
												<summary class="btn btn-sm btn-outline-secondary">
													<?= __d('queue_scheduler', 'Show output') ?> (<?= $this->Number->toReadableSize(strlen($job->output)) ?>)
												</summary>
												<pre class="mt-2 p-2 bg-light small"><?= h($job->output) ?></pre>
											</details>
										<?php } elseif ($job->failure_message) { ?>
											<details>
												<summary class="btn btn-sm btn-outline-danger">
													<?= __d('queue_scheduler', 'Show error') ?>
												</summary>
												<pre class="mt-2 p-2 bg-light small text-danger"><?= h($job->failure_message) ?></pre>
											</details>
										<?php } else { ?>
											-
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php } ?>
	</div>

		<?php if (class_exists(CronExpression::class) && $row->isCronExpression()) { ?>
		<div class="card">
			<div class="card-header">
				<i class="fas fa-terminal me-2"></i><?= __d('queue_scheduler', 'Crontab Expression') ?>
			</div>
			<div class="card-body">
				<p class="text-muted"><?= __d('queue_scheduler', 'If you want to port this into a native crontab line, copy and paste the following:') ?></p>
				<?php
					$expression = new CronExpression(SchedulerRow::normalizeCronExpression($row->frequency));
				?>
				<pre class="mb-0"><?= $expression ?></pre>
			</div>
		</div>
	<?php } ?>
</div>
