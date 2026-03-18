<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\QueueScheduler\Model\Entity\SchedulerRow> $schedulerRows
 * @var array<\Queue\Model\Entity\QueuedJob> $runningJobs
 */
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
				['class' => 'btn btn-primary', 'escape' => false],
			) ?>
		</div>
	</div>

	<p class="text-muted mb-4"><?= __('Addon to run commands and queue tasks as crontab like database driven schedule.') ?></p>

	<div class="card">
		<div class="card-header">
			<i class="fas fa-clock me-2"></i><?= __('Current Schedule') ?>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover scheduler-table mb-0">
					<thead>
						<tr>
							<th><?= __('Name') ?></th>
							<th><?= __('Frequency') ?></th>
							<th><?= __('Log') ?></th>
							<th class="actions text-end"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($schedulerRows as $schedulerRow) { ?>
							<?php
							$queuedJob = $runningJobs[$schedulerRow->job_reference] ?? null;
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
									<code><?= h($schedulerRow->frequency) ?></code>

									<?php if ($queuedJob) { ?>
										<div class="alert alert-job-running mt-2 mb-0 p-2">
											<strong><?= h($queuedJob->status) ?: __('Queued') ?></strong>
											<?= $this->Html->link(
												'<i class="fas fa-eye"></i>',
												['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id],
												['escape' => false, 'class' => 'ms-1'],
											) ?>

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
								</td>
								<td>
									<?php if ($schedulerRow->last_run) { ?>
										<div>
											<small class="text-muted"><?= __('Last Run') ?>:
												<?php if ($schedulerRow->last_queued_job_id) { ?>
													<?= $this->Html->link(
														$this->Time->nice($schedulerRow->last_run),
														['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $schedulerRow->last_queued_job_id],
														['escapeTitle' => false],
													) ?>
												<?php } else { ?>
													<?= $this->Time->nice($schedulerRow->last_run) ?>
												<?php } ?>
											</small>
										</div>
									<?php } ?>
									<?php
									$nextRun = $schedulerRow->next_run ?: $schedulerRow->calculateNextRun();
									?>
									<?php if ($nextRun) { ?>
										<div>
											<small class="text-muted"><?= __('Next Run') ?>: <?= $this->Time->nice($nextRun) ?></small>
										</div>
									<?php } ?>
								</td>
								<td class="actions text-end">
									<?php if (!$queuedJob) { ?>
										<?= $this->Form->postLink(
											'<i class="fas fa-play-circle"></i>',
											['controller' => 'SchedulerRows', 'action' => 'run', $schedulerRow->id],
											[
												'escapeTitle' => false,
												'class' => 'btn btn-sm btn-success me-1',
												'title' => __('Run manually now'),
												'confirm' => __('Sure to run it now?'),
											],
										) ?>
									<?php } ?>
									<?= $this->Form->postLink(
										'<i class="fas fa-times"></i>',
										['controller' => 'SchedulerRows', 'action' => 'edit', $schedulerRow->id],
										[
											'data' => ['enabled' => 0],
											'escapeTitle' => false,
											'class' => 'btn btn-sm btn-danger',
											'title' => __('Disable'),
											'confirm' => __('Sure to disable?'),
										],
									) ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="mt-3">
		<?= $this->Html->link(
			'<i class="fas fa-list me-1"></i>' . __('All Schedules'),
			['controller' => 'SchedulerRows', 'action' => 'index'],
			['class' => 'btn btn-secondary me-2', 'escape' => false],
		) ?>
		<?= $this->Form->postLink(
			'<i class="fas fa-times me-1"></i>' . __('Disable All'),
			['controller' => 'SchedulerRows', 'action' => 'disableAll'],
			[
				'escapeTitle' => false,
				'class' => 'btn btn-danger',
				'confirm' => __('Sure to disable all?'),
				'block' => true,
			],
		) ?>
	</div>
</div>
