<?php
/**
 * @var \App\View\AppView $this
 * @var \QueueScheduler\Model\Entity\SchedulerRow $row
 */

$windowTimeValue = static function (mixed $value): ?string {
	if ($value === null || $value === '') {
		return null;
	}
	if (is_object($value) && method_exists($value, 'format')) {
		return $value->format('H:i');
	}

	return is_string($value) ? substr($value, 0, 5) : null;
};
$selectedWindowDays = [];
if (is_string($row->window_days_of_week) && $row->window_days_of_week !== '') {
	$selectedWindowDays = array_values(array_filter(array_map('trim', explode(',', $row->window_days_of_week)), static fn (string $day): bool => $day !== ''));
}
$hasWindowValues = $row->window_start_time !== null
	|| $row->window_end_time !== null
	|| $selectedWindowDays !== [];
$hasWindowErrors = $row->getError('window_start_time')
	|| $row->getError('window_end_time')
	|| $row->getError('window_days_of_week');
$windowOpen = $hasWindowValues || $hasWindowErrors;
?>
<div class="scheduler-rows-edit">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-edit me-2"></i><?= __d('queue_scheduler', 'Edit Schedule') ?>
		</h2>
		<div>
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left me-1"></i>' . __d('queue_scheduler', 'Back'),
				['action' => 'index'],
				['class' => 'btn btn-secondary me-2', 'escapeTitle' => false],
			) ?>
			<?= $this->Form->postButton(
				'<i class="fas fa-trash me-1"></i>' . __d('queue_scheduler', 'Delete'),
				['action' => 'delete', $row->id],
				[
					'class' => 'btn btn-danger me-2',
					'escapeTitle' => false,
					'form' => [
						'class' => 'd-inline',
						'data-confirm-message' => __d('queue_scheduler', 'Are you sure you want to delete # {0}?', $row->id),
					],
				],
			) ?>
			<?= $this->Html->link(
				'<i class="fas fa-clock me-1"></i>' . __d('queue_scheduler', 'Intervals Help'),
				['controller' => 'QueueScheduler', 'action' => 'intervals'],
				['class' => 'btn btn-outline-secondary', 'escapeTitle' => false, 'target' => '_blank', 'rel' => 'noopener'],
			) ?>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<i class="fas fa-cog me-2"></i><?= __d('queue_scheduler', 'Schedule Details') ?>
		</div>
		<div class="card-body">
			<?php
			$this->Form->setConfig('errorClass', 'is-invalid');
			$this->Form->setTemplates([
				'error' => '<div class="invalid-feedback d-block" id="{{id}}">{{content}}</div>',
			]);
			?>
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
				<div class="mb-3">
					<?= $this->Form->control('job_config', [
						'type' => 'textarea',
						'rows' => 3,
						'class' => 'form-control',
					'label' => __d('queue_scheduler', 'Job Config (JSON)'),
					'placeholder' => '{"priority": 5, "group": "batch"}',
						'help' => __d('queue_scheduler', 'Optional JSON object. Allowed keys: priority (1-10, lower runs sooner; default 5) and group (worker group, matches `cake queue worker --group=...`).'),
					]) ?>
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
					<div class="card bg-light-subtle border-0 mb-3">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-center mb-2">
								<div>
									<h5 class="mb-1"><?= __d('queue_scheduler', 'Dispatch Window (optional)') ?></h5>
									<div class="small text-muted">
										<?= $hasWindowValues ? __d('queue_scheduler', 'Restrictions configured') : __d('queue_scheduler', 'No restrictions') ?>
									</div>
								</div>
								<button
									type="button"
									class="btn btn-sm btn-outline-secondary"
									data-bs-toggle="collapse"
									data-bs-target="#scheduler-window-fields"
									aria-expanded="<?= $windowOpen ? 'true' : 'false' ?>"
									aria-controls="scheduler-window-fields"
								><?= $windowOpen ? __d('queue_scheduler', 'Hide') : __d('queue_scheduler', 'Configure') ?></button>
							</div>
							<div id="scheduler-window-fields" class="collapse<?= $windowOpen ? ' show' : '' ?>">
							<div class="row">
								<div class="col-md-6 mb-3">
									<?= $this->Form->control('window_start_time', [
									'type' => 'text',
									'class' => 'form-control',
									'label' => __d('queue_scheduler', 'Start'),
									'placeholder' => '09:00',
									'value' => $windowTimeValue($row->window_start_time),
									'help' => __d('queue_scheduler', '24h format HH:MM. Leave blank for no lower bound.'),
								]) ?>
							</div>
							<div class="col-md-6 mb-3">
								<?= $this->Form->control('window_end_time', [
									'type' => 'text',
									'class' => 'form-control',
									'label' => __d('queue_scheduler', 'End'),
									'placeholder' => '18:00',
									'value' => $windowTimeValue($row->window_end_time),
									'help' => __d('queue_scheduler', '24h format HH:MM. Earlier than start means overnight.'),
								]) ?>
							</div>
						</div>
						<div class="mb-3">
							<?= $this->Form->control('window_days_of_week', [
								'type' => 'select',
								'multiple' => 'checkbox',
								'options' => [
									'1' => __d('queue_scheduler', 'Mon'),
									'2' => __d('queue_scheduler', 'Tue'),
									'3' => __d('queue_scheduler', 'Wed'),
									'4' => __d('queue_scheduler', 'Thu'),
									'5' => __d('queue_scheduler', 'Fri'),
									'6' => __d('queue_scheduler', 'Sat'),
									'0' => __d('queue_scheduler', 'Sun'),
								],
									'label' => __d('queue_scheduler', 'Allowed Days'),
									'hiddenField' => false,
									'value' => $selectedWindowDays,
									'help' => __d('queue_scheduler', 'Leave all unchecked for every day.'),
								]) ?>
							<?php if ($row->getError('window_days_of_week')) { ?>
								<div class="invalid-feedback d-block"><?= h(implode(', ', $row->getError('window_days_of_week'))) ?></div>
								<?php } ?>
								<div class="form-text"><?= __d('queue_scheduler', 'Weekday-only example: Mon-Fri.') ?></div>
							</div>
							</div>
							</div>
						</div>
					</div>
				<div class="mt-3">
					<?= $this->Form->button('<i class="fas fa-save me-1"></i>' . __d('queue_scheduler', 'Save'), ['class' => 'btn btn-primary', 'escapeTitle' => false]) ?>
				</div>
				<?= $this->Form->end() ?>
		</div>
	</div>

	<?= $this->element('QueueScheduler.content_autocomplete') ?>
</div>
