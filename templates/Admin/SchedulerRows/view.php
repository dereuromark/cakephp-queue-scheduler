<?php
/**
 * @var \App\View\AppView $this
 * @var \QueueScheduler\Model\Entity\SchedulerRow $row
 * @var array<string, mixed>|null $jobStats
 * @var array<\Queue\Model\Entity\QueuedJob> $recentJobs
 */

$intervalSec = $row->calculateIntervalSeconds();
$frequencyDescription = $row->getFrequencyDescription();
?>
<div class="scheduler-rows-view">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-calendar-check me-2"></i><?= h($row->name) ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-edit me-1"></i>' . __('Edit'),
				['action' => 'edit', $row->id],
				['class' => 'btn btn-primary me-2', 'escapeTitle' => false],
			) ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-play-circle me-1"></i>' . __('Run Now'),
				['action' => 'run', $row->id],
				[
					'class' => 'btn btn-success me-2',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline js-scheduler-run-form',
						'data-confirm-message' => __('Are you sure you want to run this now?'),
					],
				],
			) ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-trash me-1"></i>' . __('Delete'),
				['action' => 'delete', $row->id],
				[
					'class' => 'btn btn-danger',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __('Are you sure you want to delete # {0}?', $row->id),
					],
				],
			) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-info-circle me-2"></i><?= __('Schedule Details') ?>
				</div>
				<div class="card-body">
					<table class="table table-striped mb-0">
						<tr>
							<th class="scheduler-col-w-40"><?= __('Type') ?></th>
							<td><?= $row::types($row->type) ?></td>
						</tr>
						<?php if ($row->param) { ?>
							<tr>
								<th><?= __('Config') ?></th>
								<td><pre class="mb-0"><?= h(json_encode(json_decode($row->param, true), JSON_PRETTY_PRINT)) ?></pre></td>
							</tr>
						<?php } ?>
						<?php if ($row->job_config) { ?>
							<tr>
								<th><?= __('Job Config') ?></th>
								<td><pre class="mb-0"><?= h(json_encode($row->job_config, JSON_PRETTY_PRINT)) ?></pre></td>
							</tr>
						<?php } ?>
						<tr>
							<th><?= __('Frequency') ?></th>
							<td>
								<code<?= $frequencyDescription ? ' title="' . h($frequencyDescription) . '"' : '' ?>><?= h($row->frequency) ?></code>
								<?php if ($frequencyDescription) { ?>
									<div class="mt-1 text-muted"><?= h($frequencyDescription) ?></div>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<th><?= __('Enabled') ?></th>
							<td>
								<?= $this->element('QueueScheduler.yes_no', ['value' => $row->enabled]) ?>
								<?= $row->enabled ? __('Yes') : __('No') ?>
								<?php if (!$row->enabled) { ?>
									<?= $this->Form->postButton(
										'<i class="fas fa-check me-1"></i>' . __('Enable'),
										['action' => 'edit', $row->id],
										[
											'data' => ['enabled' => 1],
											'escapeTitle' => false,
											'class' => 'btn btn-sm btn-success ms-2',
											'form' => [
												'class' => 'd-inline',
												'data-confirm-message' => __('Sure to enable?'),
											],
										],
									) ?>
								<?php } ?>
							</td>
						</tr>
						<tr>
							<th><?= __('Allow Concurrent') ?></th>
							<td>
								<?= $this->element('QueueScheduler.yes_no', ['value' => $row->allow_concurrent]) ?>
								<?= $row->allow_concurrent ? __('Yes') : __('No') ?>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div class="col-lg-6 mb-4">
			<div class="card h-100">
				<div class="card-header">
					<i class="fas fa-clock me-2"></i><?= __('Timing Information') ?>
				</div>
				<div class="card-body">
					<table class="table table-striped mb-0">
						<tr>
							<th class="scheduler-col-w-40"><?= __('Last Run') ?></th>
							<td>
								<?php $lastJob = $row->last_queued_job; ?>
								<?php if (!$row->last_run) { ?>
									<span class="text-muted"><?= __('Never') ?></span>
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
						?>
						<?php if ($nextRun) { ?>
							<tr>
								<th><?= __('Next Run') ?></th>
								<td>
									<?= $this->Time->nice($nextRun) ?>
									<?php if (!$row->enabled) { ?>
										<span class="badge bg-secondary ms-1"><?= __('Disabled — won\'t run') ?></span>
									<?php } else { ?>
										<div class="small <?= $nextRunOverdue ? 'text-danger fw-semibold' : 'text-muted' ?>">
											<?= h($this->Time->timeAgoInWords($nextRun)) ?>
										</div>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<th><?= __('Created') ?></th>
							<td><?= $this->Time->nice($row->created) ?></td>
						</tr>
						<tr>
							<th><?= __('Modified') ?></th>
							<td><?= $this->Time->nice($row->modified) ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="card mb-4">
		<div class="card-header">
			<i class="fas fa-code me-2"></i><?= __('Content') ?>
		</div>
		<div class="card-body">
			<pre class="mb-0"><?= h($row->content) ?></pre>
		</div>
	</div>

	<?php if ($jobStats && $jobStats['total_runs']) { ?>
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-chart-bar me-2"></i><?= __('Job Statistics') ?>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-3 mb-3 mb-md-0">
						<div class="text-center">
							<div class="h3 mb-0"><?= $jobStats['total_runs'] ?></div>
							<small class="text-muted"><?= __('Total Runs') ?></small>
						</div>
					</div>
					<div class="col-md-3 mb-3 mb-md-0">
						<div class="text-center">
							<div class="h3 mb-0 text-success"><?= $jobStats['completed_runs'] ?: 0 ?></div>
							<small class="text-muted"><?= __('Completed') ?></small>
						</div>
					</div>
					<div class="col-md-3 mb-3 mb-md-0">
						<div class="text-center">
							<div class="h3 mb-0 text-danger"><?= $jobStats['failed_runs'] ?: 0 ?></div>
							<small class="text-muted"><?= __('Failed') ?></small>
						</div>
					</div>
					<?php if ($jobStats['avg_duration'] !== null) { ?>
						<div class="col-md-3">
							<div class="text-center">
								<div class="h3 mb-0"><?= $this->Number->precision($jobStats['avg_duration'], 1) ?>s</div>
								<small class="text-muted"><?= __('Avg Duration') ?></small>
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
			<span><i class="fas fa-history me-2"></i><?= __('Recent Executions') ?></span>
			<?php if ($recentJobs) { ?>
				<?= $this->Html->link(
					__('View All in Queue'),
					['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'index', '?' => ['search' => $row->job_reference]],
					['class' => 'btn btn-sm btn-outline-secondary'],
				) ?>
			<?php } ?>
		</div>
		<?php if (!$recentJobs) { ?>
			<div class="card-body text-center text-muted py-4">
				<?= __('No runs recorded yet.') ?>
			</div>
		<?php } else { ?>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr>
								<th><?= __('Created') ?></th>
								<th><?= __('Status') ?></th>
								<th><?= __('Duration') ?></th>
								<th><?= __('Output') ?></th>
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
												<span class="badge bg-danger"><?= __('Failed') ?></span>
											<?php } else { ?>
												<span class="badge bg-success"><?= __('Completed') ?></span>
											<?php } ?>
										<?php } elseif ($job->fetched) { ?>
											<span class="badge bg-info"><?= __('Running') ?></span>
										<?php } else { ?>
											<span class="badge bg-secondary"><?= __('Queued') ?></span>
										<?php } ?>
									</td>
									<td>
										<?php if ($job->fetched && $job->completed) { ?>
											<?php $jobDurationSec = (int)$job->fetched->diffInSeconds($job->completed); ?>
											<span class="<?= h($this->Scheduler->durationClass($jobDurationSec, $intervalSec)) ?>"><?= h($this->Scheduler->duration($jobDurationSec)) ?></span>
										<?php } elseif ($job->fetched) { ?>
											<span class="text-muted"><?= __('In progress...') ?></span>
										<?php } else { ?>
											-
										<?php } ?>
									</td>
									<td>
										<?php if ($job->output) { ?>
											<details>
												<summary class="btn btn-sm btn-outline-secondary">
													<?= __('Show output') ?> (<?= $this->Number->toReadableSize(strlen($job->output)) ?>)
												</summary>
												<pre class="mt-2 p-2 bg-light small"><?= h($job->output) ?></pre>
											</details>
										<?php } elseif ($job->failure_message) { ?>
											<details>
												<summary class="btn btn-sm btn-outline-danger">
													<?= __('Show error') ?>
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

	<?php if (class_exists('Cron\CronExpression') && $row->isCronExpression()) { ?>
		<div class="card">
			<div class="card-header">
				<i class="fas fa-terminal me-2"></i><?= __('Crontab Expression') ?>
			</div>
			<div class="card-body">
				<p class="text-muted"><?= __('If you want to port this into a native crontab line, copy and paste the following:') ?></p>
				<?php
				$expression = new \Cron\CronExpression(\QueueScheduler\Model\Entity\SchedulerRow::normalizeCronExpression($row->frequency));
				?>
				<pre class="mb-0"><?= $expression ?></pre>
			</div>
		</div>
	<?php } ?>
</div>
