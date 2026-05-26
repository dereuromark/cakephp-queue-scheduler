<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="scheduler-rows-import">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-file-import me-2"></i><?= __d('queue_scheduler', 'Import Schedule') ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left me-1"></i>' . __d('queue_scheduler', 'Back'),
				['action' => 'index'],
				['class' => 'btn btn-secondary', 'escapeTitle' => false],
			) ?>
		</div>
	</div>

	<div class="card border-secondary">
		<div class="card-header bg-light">
			<i class="fas fa-file-import me-2"></i><?= __d('queue_scheduler', 'Import Schedule Draft') ?>
		</div>
		<div class="card-body">
			<p class="text-muted small mb-3">
				<?= __d('queue_scheduler', 'Import a previously exported schedule JSON. The scheduler will redirect to the add form with the fields pre-filled so you can review or customize before saving.') ?>
			</p>
			<?= $this->Form->create(null, [
				'url' => ['action' => 'import'],
				'type' => 'file',
			]) ?>
			<div class="row">
				<div class="col-md-6 mb-3">
					<?= $this->Form->control('import_file', [
						'type' => 'file',
						'label' => __d('queue_scheduler', 'JSON File'),
						'accept' => '.json,application/json',
						'class' => 'form-control',
					]) ?>
				</div>
				<div class="col-md-6 mb-3">
					<?= $this->Form->control('import_json', [
						'type' => 'textarea',
						'label' => __d('queue_scheduler', 'Or Paste JSON'),
						'rows' => 8,
						'class' => 'form-control font-monospace',
						'placeholder' => '{"row":{"name":"Nightly job"}}',
					]) ?>
				</div>
			</div>
			<?= $this->Form->button(
				'<i class="fas fa-upload me-1"></i>' . __d('queue_scheduler', 'Load Into Add Form'),
				['class' => 'btn btn-secondary', 'escapeTitle' => false],
			) ?>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
