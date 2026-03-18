<?php
/**
 * @var \App\View\AppView $this
 */

/**
 * @var \QueueScheduler\View\Helper\SchedulerHelper $scheduler
 */
$scheduler = $this->Scheduler;
?>
<div class="row">
	<div class="col-md-6 mb-3 mb-md-0">
		<h5><i class="fas fa-terminal me-2"></i><?= __('Available Commands') ?></h5>
		<ul class="list-unstyled">
			<?php foreach ($scheduler->availableCommands() as $name => $command) { ?>
				<li class="mb-2">
					<strong><?= h($name) ?></strong><br>
					<code class="small"><?= h($command) ?></code>
				</li>
			<?php } ?>
		</ul>
	</div>

	<div class="col-md-6">
		<h5><i class="fas fa-tasks me-2"></i><?= __('Available Queue Tasks') ?></h5>
		<ul class="list-unstyled">
			<?php foreach ($scheduler->availableQueueTasks() as $name => $queueTask) { ?>
				<li class="mb-2">
					<strong><?= h($name) ?></strong><br>
					<code class="small"><?= h($queueTask) ?></code>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>
