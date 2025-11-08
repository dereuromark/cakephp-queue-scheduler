<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\QueueScheduler\Model\Entity\SchedulerRow> $schedulerRows
 * @var array<\Queue\Model\Entity\QueuedJob> $runningJobs
 * @var array<\QueueScheduler\Model\Entity\CommandLog> $commandLogs
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __('Actions') ?></li>
		<li class="nav-item">
			<?= $this->Html->link(__('New {0}', __('Row')), ['controller' => 'SchedulerRows', 'action' => 'add'], ['class' => 'nav-link']) ?>
			<?= $this->Html->link(__('Available {0}', __('Intervals')), ['action' => 'intervals'], ['class' => 'nav-link']) ?>
		</li>
	</ul>
</nav>
<div class="rows index content large-9 medium-8 columns col-sm-8 col-12">

	<h2><?= __('Queue Scheduler') ?></h2>
	<p>Addon to run commands and queue tasks as crontab like database driven schedule.</p>

	<h3>Current schedule</h3>
	<table class="table table-sm table-striped">
		<thead>
		<tr>
			<th><?= 'Name' ?></th>
			<th><?= 'Frequency' ?></th>
			<th><?= 'Log' ?></th>
			<th class="actions"><?= __('Actions') ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($schedulerRows as $schedulerRow) { ?>
			<?php
			$queuedJob = $runningJobs[$schedulerRow->job_reference] ?? null;
			?>
			<tr>
				<td>
					<?= $this->Html->link($schedulerRow->name, ['controller' => 'SchedulerRows', 'action' => 'view', $schedulerRow->id]) ?>
					<div>
						<small><?= $schedulerRow::types($schedulerRow->type) ?></small>
					</div>
				</td>
				<td>
					<?= h($schedulerRow->frequency) ?>

					<?php if ($queuedJob) { ?>
					<div class="alert alert-warning">
						<b><?php echo h($queuedJob->status) ?: 'Queued'?></b>
						<?php echo $this->Html->link($this->Icon->render('view'), ['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $queuedJob->id], ['escapeTitle' => false]); ?>

						<?php if (!$queuedJob->completed && $queuedJob->fetched) { ?>
							<?php if (!$queuedJob->failure_message) { ?>
								<?php echo $this->QueueProgress->progress($queuedJob) ?>
								<br>
								<?php
								$textProgressBar = $this->QueueProgress->progressBar($queuedJob, 18);
								echo $this->QueueProgress->htmlProgressBar($queuedJob, $textProgressBar);
								?>
							<?php } else { ?>
								<i><?php echo $this->Queue->failureStatus($queuedJob); ?></i>
							<?php } ?>
						<?php } ?>
					</div>
					<?php } ?>
				</td>
				<td>
					<?php if ($schedulerRow->last_run) { ?>
						<div>
							<small><?= __('Last Run') ?>: <?php echo $this->Time->nice($schedulerRow->last_run); ?></small>
							<?php
							$lastLog = $commandLogs[$schedulerRow->content] ?? null;
							if ($lastLog) {
								if (!$lastLog->success) {
									$errorTitle = h($lastLog->error_message ?: 'Command failed');
									?>
									<span class="badge bg-danger ms-1" title="<?= $errorTitle ?>" data-bs-toggle="tooltip">
										<?= $this->Icon->render('error', [], ['title' => 'Failed']) ?>
									</span>
									<?php
								} else {
									?>
									<span class="badge bg-success ms-1" title="Success" data-bs-toggle="tooltip">
										<?= $this->Icon->render('yes', [], ['title' => 'Success']) ?>
									</span>
									<?php
								}
							}
							?>
						</div>
						<?php if ($lastLog && !$lastLog->success) { ?>
							<div class="alert alert-danger mt-2 mb-0" style="font-size: 0.85em;">
								<strong>Error:</strong>
								<?php
								$errorMsg = $lastLog->error_message ?: 'Unknown error';
								if (strlen($errorMsg) > 150) {
									$errorMsg = substr($errorMsg, 0, 150) . '...';
								}
								echo h($errorMsg);
								?>
								<?php if ($lastLog->stderr) { ?>
									<details class="mt-1">
										<summary style="cursor: pointer;">Show stderr output</summary>
										<pre style="font-size: 0.75em; max-height: 200px; overflow-y: auto;"><?= h($lastLog->stderr) ?></pre>
									</details>
								<?php } ?>
							</div>
						<?php } ?>
					<?php } ?>
					<?php
						$nextRun = $schedulerRow->next_run ?: $schedulerRow->calculateNextRun();
					?>
					<?php if ($nextRun) { ?>
						<div><small><?= __('Next Run') ?>: <?php echo $this->Time->nice($nextRun); ?></small></div>
					<?php } ?>
				</td>
				<td class="actions">
					<?php if (!$queuedJob) { ?>
						<?php echo $this->Form->postLink($this->Icon->render('play-circle', [], ['title' => 'Run manually now']), ['controller' => 'SchedulerRows', 'action' => 'run', $schedulerRow->id], ['escapeTitle' => false, 'class' => 'btn btn-small btn-success', 'confirm' => 'Sure to run it now?']); ?>
					<?php } ?>
					<?php echo $this->Form->postLink($this->Icon->render('no', [], ['title' => 'Disable']), ['controller' => 'SchedulerRows', 'action' => 'edit', $schedulerRow->id], ['data' => ['enabled' => 0], 'escapeTitle' => false, 'class' => 'btn btn-small btn-danger', 'confirm' => 'Sure to disable?']); ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>


	<p>
	<?= $this->Html->link(__('Details'), ['controller' => 'SchedulerRows', 'action' => 'index'], ['class' => 'btn btn-secondary']) ?> <?php echo $this->Form->postLink($this->Icon->render('no', [], ['title' => 'Disable']) . ' ' . __('Disable all'), ['controller' => 'SchedulerRows', 'action' => 'disableAll'], ['escapeTitle' => false, 'class' => 'btn btn-small btn-danger', 'block' => true,  'confirm' => 'Sure to disable all?']); ?>
	</p>

</div>
