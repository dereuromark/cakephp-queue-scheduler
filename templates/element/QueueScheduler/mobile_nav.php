<?php
/**
 * Mobile navigation element for QueueScheduler admin.
 *
 * @var \Cake\View\View $this
 */

$request = $this->getRequest();
$controller = $request ? $request->getParam('controller') : '';
$action = $request ? $request->getParam('action') : '';
?>
<nav class="nav flex-column py-3">
	<div class="px-3 mb-2">
		<small class="text-white-50 text-uppercase fw-semibold"><?= __d('queue_scheduler', 'Dashboard') ?></small>
	</div>
	<a class="nav-link text-white<?= $controller === 'QueueScheduler' && $action === 'index' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'index']) ?>">
		<i class="fas fa-tachometer-alt me-2"></i><?= __d('queue_scheduler', 'Overview') ?>
	</a>

	<div class="px-3 mt-3 mb-2">
		<small class="text-white-50 text-uppercase fw-semibold"><?= __d('queue_scheduler', 'Scheduled Jobs') ?></small>
	</div>
	<a class="nav-link text-white<?= $controller === 'SchedulerRows' && $action === 'index' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'SchedulerRows', 'action' => 'index']) ?>">
		<i class="fas fa-list me-2"></i><?= __d('queue_scheduler', 'All Schedules') ?>
	</a>
	<a class="nav-link text-white<?= $controller === 'SchedulerRows' && $action === 'add' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'SchedulerRows', 'action' => 'add']) ?>">
		<i class="fas fa-plus me-2"></i><?= __d('queue_scheduler', 'Add Schedule') ?>
	</a>

	<div class="px-3 mt-3 mb-2">
		<small class="text-white-50 text-uppercase fw-semibold"><?= __d('queue_scheduler', 'Reference') ?></small>
	</div>
	<a class="nav-link text-white<?= $controller === 'QueueScheduler' && $action === 'intervals' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'intervals']) ?>">
		<i class="fas fa-clock me-2"></i><?= __d('queue_scheduler', 'Intervals Help') ?>
	</a>

	<?php if (\Cake\Core\Plugin::isLoaded('Queue')): ?>
	<div class="px-3 mt-3 mb-2">
		<small class="text-white-50 text-uppercase fw-semibold"><?= __d('queue_scheduler', 'Queue Plugin') ?></small>
	</div>
	<a class="nav-link text-white" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'index']) ?>">
		<i class="fas fa-layer-group me-2"></i><?= __d('queue_scheduler', 'Queue Dashboard') ?>
	</a>
	<a class="nav-link text-white" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'QueuedJobs', 'action' => 'index']) ?>">
		<i class="fas fa-tasks me-2"></i><?= __d('queue_scheduler', 'Queued Jobs') ?>
	</a>
	<?php endif; ?>
</nav>
