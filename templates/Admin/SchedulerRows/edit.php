<?php
/**
 * @var \App\View\AppView $this
 * @var \QueueScheduler\Model\Entity\SchedulerRow $row
 */
?>
<div class="scheduler-rows-edit">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-edit me-2"></i><?= __('Edit Schedule') ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left me-1"></i>' . __('Back'),
				['action' => 'index'],
				['class' => 'btn btn-secondary me-2', 'escape' => false],
			) ?>
			<?= $this->Form->postLink(
				'<i class="fas fa-trash me-1"></i>' . __('Delete'),
				['action' => 'delete', $row->id],
				['class' => 'btn btn-danger me-2', 'escape' => false, 'confirm' => __('Are you sure you want to delete # {0}?', $row->id)],
			) ?>
			<?= $this->Html->link(
				'<i class="fas fa-clock me-1"></i>' . __('Intervals Help'),
				['controller' => 'QueueScheduler', 'action' => 'intervals'],
				['class' => 'btn btn-outline-secondary', 'escape' => false],
			) ?>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<i class="fas fa-cog me-2"></i><?= __('Schedule Details') ?>
		</div>
		<div class="card-body">
			<?= $this->Form->create($row) ?>
			<div class="row">
				<div class="col-md-6 mb-3">
					<?= $this->Form->control('name', ['class' => 'form-control']) ?>
				</div>
				<div class="col-md-6 mb-3">
					<?= $this->Form->control('type', ['options' => $row::types(), 'class' => 'form-select']) ?>
				</div>
			</div>
			<div class="mb-3">
				<?= $this->Form->control('content', ['type' => 'text', 'class' => 'form-control']) ?>
			</div>
			<div class="mb-3">
				<?= $this->Form->control('param', ['class' => 'form-control']) ?>
			</div>
			<div class="row">
				<div class="col-md-6 mb-3">
					<?= $this->Form->control('frequency', ['list' => 'frequency-suggestions', 'class' => 'form-control']) ?>
				</div>
				<div class="col-md-3 mb-3">
					<?= $this->Form->control('allow_concurrent', ['class' => 'form-check-input']) ?>
				</div>
				<div class="col-md-3 mb-3">
					<?= $this->Form->control('enabled', ['class' => 'form-check-input']) ?>
				</div>
			</div>
			<div class="mt-3">
				<?= $this->Form->button('<i class="fas fa-save me-1"></i>' . __('Save'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>

	<?= $this->element('QueueScheduler.content_autocomplete') ?>

	<div class="card mt-4">
		<div class="card-header">
			<a class="text-decoration-none" data-bs-toggle="collapse" href="#available-content" role="button" aria-expanded="false" aria-controls="available-content">
				<i class="fas fa-list me-2"></i><?= __('Available Commands & Tasks') ?> <small>&#9660;</small>
			</a>
		</div>
		<div class="collapse" id="available-content">
			<div class="card-body">
				<?= $this->element('QueueScheduler.available') ?>
			</div>
		</div>
	</div>
</div>
