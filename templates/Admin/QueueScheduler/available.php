<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="queue-scheduler-available">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="mb-0">
			<i class="fas fa-list me-2"></i><?= __d('queue_scheduler', 'Available Commands & Tasks') ?>
		</h2>
	</div>

	<div class="card">
		<div class="card-body">
			<?= $this->element('QueueScheduler.available') ?>
		</div>
	</div>
</div>
