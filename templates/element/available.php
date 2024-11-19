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
	<div class="col-md-6">
		<h3>Available Commands</h3>
		<ul>
			<?php foreach ($scheduler->availableCommands() as $commmand) {  ?>
				<li>
					<p>
						<?php echo h($commmand); ?>
					</p>
				</li>
			<?php } ?>
		</ul>

	</div>

	<div class="col-md-6">
		<h3>Available Queue Tasks</h3>
		<ul>
			<?php foreach ($scheduler->availableQueueTasks() as $name => $queueTask) {  ?>
				<li>
					<p>
						<?php echo h($name); ?>
					</p>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>
