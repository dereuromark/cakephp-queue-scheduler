<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\QueueScheduler\Model\Entity\SchedulerRow> $rows
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
	<ul class="side-nav nav nav-pills flex-column">
		<li class="nav-item heading"><?= __('Actions') ?></li>
		<li class="nav-item">
			<?= $this->Html->link(__('Dashboard'), ['controller' => 'QueueScheduler', 'action' => 'index'], ['class' => 'nav-link']) ?>
		</li>
		<li class="nav-item">
			<?= $this->Html->link(__('New {0}', __('Row')), ['action' => 'add'], ['class' => 'nav-link']) ?>
		</li>
	</ul>
</nav>
<div class="rows index content large-9 medium-8 columns col-sm-8 col-12">

	<h2><?= __('Rows') ?></h2>

	<div class="">
		<table class="table table-sm table-striped">
			<thead>
				<tr>
					<th><?= $this->Paginator->sort('name') ?></th>
					<th><?= $this->Paginator->sort('type') ?></th>
					<th><?= $this->Paginator->sort('frequency') ?></th>
					<th><?= $this->Paginator->sort('allow_concurrent') ?></th>
					<th><?= $this->Paginator->sort('enabled') ?></th>
					<th><?= $this->Paginator->sort('created', null, ['direction' => 'desc']) ?></th>
					<th><?= $this->Paginator->sort('modified', null, ['direction' => 'desc']) ?></th>
					<th class="actions"><?= __('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $row): ?>
				<tr>
					<td><?= h($row->name) ?></td>
					<td>
						<?= $row::types($row->type) ?>
						<div><small>
                            <?php echo h($row->param); ?>
						</small></div>
					</td>
					<td><?= h($row->frequency) ?></td>
					<td><?= $this->element('QueueScheduler.yes_no', ['value' => $row->allow_concurrent]) ?></td>
					<td>
						<?= $this->element('QueueScheduler.yes_no', ['value' => $row->enabled]) ?>
						<?php if ($row->enabled && $row->type === $row::TYPE_SHELL_COMMAND && !\Cake\Core\Configure::read('QueueScheduler.allowRaw')) { ?>
							<span><?php echo $this->Icon->render('stop-circle', [], ['title' => 'Raw commands are currently configured to be not runnable on non-debug system for security reasons.']); ?></span>
						<?php } ?>

						<?php if ($row->last_run) { ?>
						<div><small><?= __('Last Run') ?>: <?php echo $this->Time->nice($row->last_run); ?></small></div>
						<?php } ?>
						<?php
							$nextRun = $row->next_run ?: $row->calculateNextRun();
						?>
						<?php if ($nextRun) { ?>
							<?php if (!$row->enabled) { ?>
								<div class="canceled"><small><del><?= __('Next Run') ?>: <?php echo $this->Time->nice($nextRun); ?></del></small></div>
							<?php } else { ?>
								<div><small><?= __('Next Run') ?>: <?php echo $this->Time->nice($nextRun); ?></small></div>
							<?php } ?>
						<?php } ?>
					</td>
					<td><?= $this->Time->nice($row->created) ?></td>
					<td><?= $this->Time->nice($row->modified) ?></td>
					<td class="actions">
						<?php echo $this->Html->link($this->Icon->render('view'), ['action' => 'view', $row->id], ['escapeTitle' => false]); ?>
						<?php echo $this->Html->link($this->Icon->render('edit'), ['action' => 'edit', $row->id], ['escapeTitle' => false]); ?>
						<?php echo $this->Form->postLink($this->Icon->render('delete'), ['action' => 'delete', $row->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to delete # {0}?', $row->id)]); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php echo $this->element('Tools.pagination'); ?>
</div>
