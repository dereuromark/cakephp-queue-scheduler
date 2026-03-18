<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\QueueScheduler\Model\Entity\SchedulerRow> $rows
 */
?>
<div class="scheduler-rows-index">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-list me-2"></i><?= __('All Schedules') ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-plus me-1"></i>' . __('New Schedule'),
				['action' => 'add'],
				['class' => 'btn btn-primary', 'escape' => false],
			) ?>
		</div>
	</div>

	<div class="card">
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover scheduler-table mb-0">
					<thead>
						<tr>
							<th><?= $this->Paginator->sort('name') ?></th>
							<th><?= $this->Paginator->sort('type') ?></th>
							<th><?= $this->Paginator->sort('frequency') ?></th>
							<th><?= $this->Paginator->sort('allow_concurrent') ?></th>
							<th><?= $this->Paginator->sort('enabled') ?></th>
							<th><?= $this->Paginator->sort('created', null, ['direction' => 'desc']) ?></th>
							<th><?= $this->Paginator->sort('modified', null, ['direction' => 'desc']) ?></th>
							<th class="actions text-end"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $row) { ?>
							<tr>
								<td>
									<?= h($row->name) ?>
									<div>
										<small class="text-muted"><?= h($row->content) ?></small>
									</div>
								</td>
								<td>
									<?= $row::types($row->type) ?>
									<?php if ($row->param) { ?>
										<div><small class="text-muted"><?= h($row->param) ?></small></div>
									<?php } ?>
								</td>
								<td>
									<code><?= h($row->frequency) ?></code>
									<?php if (class_exists('Cron\CronExpression') && $row->isCronExpression()) { ?>
										<?php if (class_exists('Panlatent\CronExpressionDescriptor\ExpressionDescriptor')) { ?>
											<div>
												<small class="text-muted">
													<?php
													$expression = new \Cron\CronExpression($row->frequency);
													$locale = Locale::getDefault();
													echo (new \Panlatent\CronExpressionDescriptor\ExpressionDescriptor($expression, $locale, true))->getDescription();
													?>
												</small>
											</div>
										<?php } ?>
									<?php } ?>
								</td>
								<td><?= $this->element('QueueScheduler.yes_no', ['value' => $row->allow_concurrent]) ?></td>
								<td>
									<?= $this->element('QueueScheduler.yes_no', ['value' => $row->enabled]) ?>
									<?php if ($row->enabled && $row->type === $row::TYPE_SHELL_COMMAND && !\Cake\Core\Configure::read('QueueScheduler.allowRaw')) { ?>
										<span class="text-warning" title="<?= __('Raw commands are currently configured to be not runnable on non-debug system for security reasons.') ?>">
											<i class="fas fa-exclamation-triangle"></i>
										</span>
									<?php } elseif (!$row->enabled && !($row->type === $row::TYPE_SHELL_COMMAND && !\Cake\Core\Configure::read('QueueScheduler.allowRaw'))) { ?>
										<?= $this->Form->postLink(
											'<i class="fas fa-check me-1"></i>' . __('Enable'),
											['action' => 'edit', $row->id],
											[
												'data' => ['enabled' => 1],
												'escapeTitle' => false,
												'class' => 'btn btn-sm btn-success',
												'confirm' => __('Sure to enable?'),
											],
										) ?>
									<?php } ?>

									<?php if ($row->last_run) { ?>
										<div>
											<small class="text-muted"><?= __('Last Run') ?>:
												<?php if ($row->last_queued_job_id) { ?>
													<?= $this->Html->link(
														$this->Time->nice($row->last_run),
														['plugin' => 'Queue', 'controller' => 'QueuedJobs', 'action' => 'view', $row->last_queued_job_id],
														['escapeTitle' => false],
													) ?>
												<?php } else { ?>
													<?= $this->Time->nice($row->last_run) ?>
												<?php } ?>
											</small>
										</div>
									<?php } ?>
									<?php
									$nextRun = $row->next_run ?: $row->calculateNextRun();
									?>
									<?php if ($nextRun) { ?>
										<?php if (!$row->enabled) { ?>
											<div><small class="next-run-canceled"><?= __('Next Run') ?>: <?= $this->Time->nice($nextRun) ?></small></div>
										<?php } else { ?>
											<div><small class="text-muted"><?= __('Next Run') ?>: <?= $this->Time->nice($nextRun) ?></small></div>
										<?php } ?>
									<?php } ?>
								</td>
								<td><small><?= $this->Time->nice($row->created) ?></small></td>
								<td><small><?= $this->Time->nice($row->modified) ?></small></td>
								<td class="actions text-end">
									<?= $this->Html->link(
										'<i class="fas fa-eye"></i>',
										['action' => 'view', $row->id],
										['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-secondary me-1', 'title' => __('View')],
									) ?>
									<?= $this->Html->link(
										'<i class="fas fa-edit"></i>',
										['action' => 'edit', $row->id],
										['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-secondary me-1', 'title' => __('Edit')],
									) ?>
									<?= $this->Form->postLink(
										'<i class="fas fa-play-circle"></i>',
										['action' => 'run', $row->id],
										['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-success me-1', 'title' => __('Run manually now'), 'confirm' => __('Sure to run it now?')],
									) ?>
									<?= $this->Form->postLink(
										'<i class="fas fa-trash"></i>',
										['action' => 'delete', $row->id],
										['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-danger', 'title' => __('Delete'), 'confirm' => __('Are you sure you want to delete # {0}?', $row->id)],
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
		<?= $this->element('Tools.pagination') ?>
	</div>
</div>
