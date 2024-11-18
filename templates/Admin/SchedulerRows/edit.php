<?php
/**
 * @var \App\View\AppView $this
 * @var \QueueScheduler\Model\Entity\SchedulerRow $row
 */
?>
<div class="row">
	<aside class="column large-3 medium-4 columns col-sm-4 col-12">
		<ul class="side-nav nav nav-pills flex-column">
			<li class="nav-item heading"><?= __('Actions') ?></li>
			<li class="nav-item"><?= $this->Form->postLink(
				__('Delete'),
				['action' => 'delete', $row->id],
				['confirm' => __('Are you sure you want to delete # {0}?', $row->id), 'class' => 'side-nav-item']
				) ?></li>
			<li class="nav-item"><?= $this->Html->link(__('List Rows'), ['action' => 'index'], ['class' => 'side-nav-item']) ?></li>
		</ul>
	</aside>
	<div class="column-responsive column-80 form large-9 medium-8 columns col-sm-8 col-12">
		<div class="rows form content">
			<h2><?= __('Rows') ?></h2>

			<?= $this->Form->create($row) ?>
			<fieldset>
				<legend><?= __('Edit Row') ?></legend>
				<?php
					echo $this->Form->control('name');
					echo $this->Form->control('type', ['options' => $row::types()]);
					echo $this->Form->control('content');
					echo $this->Form->control('param');
					echo $this->Form->control('frequency');
					echo $this->Form->control('allow_concurrent');

					echo $this->Form->control('enabled');
				?>
			</fieldset>
			<?= $this->Form->button(__('Submit')) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
