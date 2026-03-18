<?php
/**
 * @var \App\View\AppView $this
 */

use QueueScheduler\Model\Entity\SchedulerRow;

/**
 * @var \QueueScheduler\View\Helper\SchedulerHelper $scheduler
 */
$scheduler = $this->Scheduler;
?>
<div class="card mb-4">
	<div class="card-header">
		<a class="text-decoration-none" data-bs-toggle="collapse" href="#quick-add-body" role="button" aria-expanded="false" aria-controls="quick-add-body">
			<i class="fas fa-bolt me-2"></i><?= __('Quick Add') ?> <small>&#9660;</small>
		</a>
	</div>
	<div class="collapse" id="quick-add-body">
		<div class="card-body">
			<div class="row">
				<div class="col-md-6 mb-3 mb-md-0">
					<h5><i class="fas fa-terminal me-2"></i><?= __('Cake Commands') ?></h5>
					<div class="list-group">
						<?php foreach ($scheduler->availableCommands() as $name => $command) { ?>
							<?= $this->Html->link(
								'<strong>' . h($name) . '</strong><br><small class="text-muted"><code>' . h($command) . '</code></small>',
								['action' => 'add', '?' => [
									'type' => SchedulerRow::TYPE_CAKE_COMMAND,
									'content' => $command,
									'name' => $name,
								]],
								['class' => 'list-group-item list-group-item-action', 'escape' => false],
							) ?>
						<?php } ?>
					</div>
				</div>

				<div class="col-md-6">
					<h5><i class="fas fa-tasks me-2"></i><?= __('Queue Tasks') ?></h5>
					<div class="list-group">
						<?php foreach ($scheduler->availableQueueTasks() as $name => $queueTask) { ?>
							<?= $this->Html->link(
								'<strong>' . h($name) . '</strong><br><small class="text-muted"><code>' . h($queueTask) . '</code></small>',
								['action' => 'add', '?' => [
									'type' => SchedulerRow::TYPE_QUEUE_TASK,
									'content' => $queueTask,
									'name' => $name,
								]],
								['class' => 'list-group-item list-group-item-action', 'escape' => false],
							) ?>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
