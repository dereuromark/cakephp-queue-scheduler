<?php
/**
 * @var \App\View\AppView $this
 * @var \QueueScheduler\Model\Entity\SchedulerRow $row
 * @var array<string, mixed>|null $jobStats
 * @var array<\Queue\Model\Entity\QueuedJob> $recentJobs
 */
?>
<div class="row">
	<aside class="column actions large-3 medium-4 col-sm-4 col-xs-12">
		<ul class="side-nav nav nav-pills flex-column">
			<li class="nav-item heading"><?= __('Actions') ?></li>
			<li class="nav-item"><?= $this->Html->link(__('Edit {0}', __('Row')), ['action' => 'edit', $row->id], ['class' => 'nav-link']) ?></li>
			<li class="nav-item"><?= $this->Form->postLink(__('Run {0}', __('manually')), ['action' => 'run', $row->id], ['confirm' => __('Are you sure you want to run this now?'), 'class' => 'nav-link']) ?></li>
			<li class="nav-item"><?= $this->Form->postLink(__('Delete {0}', __('Row')), ['action' => 'delete', $row->id], ['confirm' => __('Are you sure you want to delete # {0}?', $row->id), 'class' => 'nav-link']) ?></li>
			<li class="nav-item"><?= $this->Html->link(__('List {0}', __('Rows')), ['action' => 'index'], ['class' => 'nav-link']) ?></li>
			<li class="nav-item"><?= $this->Html->link(__('View Jobs in Queue'), ['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'index', '?' => ['search' => $row->job_reference]], ['class' => 'nav-link']) ?></li>
		</ul>
	</aside>
	<div class="column-responsive column-80 content large-9 medium-8 col-sm-8 col-xs-12">
		<div class="rows view content">
			<h2><?= h($row->name) ?></h2>

			<table class="table table-striped">
				<tr>
					<th><?= __('Type') ?></th>
					<td><?= $row::types($row->type) ?></td>
				</tr>
				<?php if ($row->config) { ?>
				<tr>
					<th><?= __('Config') ?></th>
					<td><pre><?= json_encode(json_decode($row->param, true), JSON_PRETTY_PRINT) ?></pre></td>
				</tr>
				<?php } ?>
				<tr>
					<th><?= __('Frequency') ?></th>
					<td>
						<p>
						<code><?= h($row->frequency) ?></code>
						</p>

						<?php if (class_exists('Cron\CronExpression') && $row->isCronExpression()) { ?>
							<?php if (class_exists('Panlatent\CronExpressionDescriptor\ExpressionDescriptor') && $row->isCronExpression()) { ?>
								<p class="human-readable-description">
									<?php
									// Normalize @minutely to standard cron expression
								$frequency = $row->frequency === '@minutely' ? '* * * * *' : $row->frequency;
								$expression = (new \Cron\CronExpression($frequency));
									$locale = Locale::getDefault();
									?>
									<?php echo (new \Panlatent\CronExpressionDescriptor\ExpressionDescriptor($expression, $locale, true))->getDescription();?>
								</p>
							<?php } ?>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th><?= __('Enabled') ?></th>
					<td>
						<?= $this->element('QueueScheduler.yes_no', ['value' => $row->enabled]) ?> <?= $row->enabled ? __('Yes') : __('No'); ?>

						<?php if (!$row->enabled) { ?>
							<?php echo $this->Form->postLink($this->element('QueueScheduler.icon', ['name' => 'yes', 'attributes' => ['title' => 'Enable']]) . ' ' . __('Enable'), ['controller' => 'SchedulerRows', 'action' => 'edit', $row->id], ['data' => ['enabled' => 1], 'escapeTitle' => false, 'class' => 'btn btn-small btn-success', 'confirm' => 'Sure to enable?']); ?>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th><?= __('Last Run') ?></th>
					<td><?= $this->Time->nice($row->last_run) ?></td>
				</tr>
				<?php
				$nextRun = $row->next_run ?: $row->calculateNextRun();
				?>
				<?php if ($nextRun) { ?>
					<tr>
						<th><?= __('Next Run') ?></th>
						<td>
							<?php if (!$row->enabled) { ?>
								<div class="canceled"><del><?php echo $this->Time->nice($nextRun); ?></del></div>
							<?php } else { ?>
								<div><?php echo $this->Time->nice($nextRun); ?></div>
							<?php } ?>
							<div>
							(<?php echo $this->Time->timeAgoInWords($nextRun); ?>)
							</div>
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
				<tr>
					<th><?= __('Allow Concurrent') ?></th>
					<td><?= $this->element('QueueScheduler.yes_no', ['value' => $row->allow_concurrent]) ?> <?= $row->allow_concurrent ? __('Yes') : __('No'); ?></td>
				</tr>
			</table>
			<div class="text">
				<strong><?= __('Content') ?></strong>
				<blockquote>
					<?= $this->Text->autoParagraph(h($row->content)); ?>
				</blockquote>
			</div>

			<?php if ($jobStats && $jobStats['total_runs']) { ?>
			<h3><?= __('Job Statistics') ?></h3>
			<table class="table table-striped">
				<tr>
					<th><?= __('Total Runs') ?></th>
					<td><?= $jobStats['total_runs'] ?></td>
				</tr>
				<tr>
					<th><?= __('Completed') ?></th>
					<td><?= $jobStats['completed_runs'] ?: 0 ?></td>
				</tr>
				<tr>
					<th><?= __('Failed') ?></th>
					<td><?= $jobStats['failed_runs'] ?: 0 ?></td>
				</tr>
				<?php if ($jobStats['avg_duration'] !== null) { ?>
				<tr>
					<th><?= __('Average Duration') ?></th>
					<td><?= $this->Number->precision($jobStats['avg_duration'], 1) ?> <?= __('seconds') ?></td>
				</tr>
				<tr>
					<th><?= __('Min / Max Duration') ?></th>
					<td><?= $jobStats['min_duration'] ?> / <?= $jobStats['max_duration'] ?> <?= __('seconds') ?></td>
				</tr>
				<?php } ?>
			</table>
			<?php } ?>

			<?php if ($recentJobs) { ?>
			<h3><?= __('Recent Executions') ?></h3>
			<table class="table table-striped">
				<thead>
					<tr>
						<th><?= __('Created') ?></th>
						<th><?= __('Status') ?></th>
						<th><?= __('Duration') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($recentJobs as $job) { ?>
					<tr>
						<td><?= $this->Time->nice($job->created) ?></td>
						<td>
							<?php if ($job->completed) { ?>
								<?php if ($job->failure_message) { ?>
									<span class="badge badge-danger bg-danger"><?= __('Failed') ?></span>
								<?php } else { ?>
									<span class="badge badge-success bg-success"><?= __('Completed') ?></span>
								<?php } ?>
							<?php } elseif ($job->fetched) { ?>
								<span class="badge badge-info bg-info"><?= __('Running') ?></span>
							<?php } else { ?>
								<span class="badge badge-secondary bg-secondary"><?= __('Queued') ?></span>
							<?php } ?>
						</td>
						<td>
							<?php if ($job->fetched && $job->completed) { ?>
								<?= $job->fetched->diffInSeconds($job->completed) ?> <?= __('seconds') ?>
							<?php } elseif ($job->fetched) { ?>
								<?= __('In progress...') ?>
							<?php } else { ?>
								-
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<?php } ?>

			<?php if (class_exists('Cron\CronExpression') && $row->isCronExpression()) { ?>
			<h3>Crontab expression</h3>
			<p>If you want to port this into a native crontab line, copy and paste the following</p>

			<?php
				// Normalize @minutely to standard cron expression
			$frequency = $row->frequency === '@minutely' ? '* * * * *' : $row->frequency;
			$expression = (new \Cron\CronExpression($frequency));
			?>
			<pre class="crontab"><?php echo $expression; ?></pre>
			<?php } ?>
		</div>
	</div>
</div>
