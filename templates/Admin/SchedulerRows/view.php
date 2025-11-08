<?php
/**
 * @var \App\View\AppView $this
 * @var \QueueScheduler\Model\Entity\SchedulerRow $row
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
									$expression = (new \Cron\CronExpression($row->frequency));
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
							<?php echo $this->Form->postLink($this->Icon->render('yes', [], ['title' => 'Enable']) . ' ' . __('Enable'), ['controller' => 'SchedulerRows', 'action' => 'edit', $row->id], ['data' => ['enabled' => 1], 'escapeTitle' => false, 'class' => 'btn btn-small btn-success', 'confirm' => 'Sure to enable?']); ?>
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

			<?php if (class_exists('Cron\CronExpression') && $row->isCronExpression()) { ?>
			<h3>Crontab expression</h3>
			<p>If you want to port this into a native crontab line, copy and paste the following</p>

			<?php
				$expression = (new \Cron\CronExpression($row->frequency));
			?>
			<pre class="crontab"><?php echo $expression; ?></pre>
			<?php } ?>
		</div>
	</div>
</div>
