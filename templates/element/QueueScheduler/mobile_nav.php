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
		<small class="text-white-50 text-uppercase fw-semibold"><?= __('Dashboard') ?></small>
	</div>
	<a class="nav-link text-white<?= $controller === 'QueueScheduler' && $action === 'index' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'index']) ?>">
		<i class="fas fa-tachometer-alt me-2"></i><?= __('Overview') ?>
	</a>

	<div class="px-3 mt-3 mb-2">
		<small class="text-white-50 text-uppercase fw-semibold"><?= __('Scheduled Jobs') ?></small>
	</div>
	<a class="nav-link text-white<?= $controller === 'SchedulerRows' && $action === 'index' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'SchedulerRows', 'action' => 'index']) ?>">
		<i class="fas fa-list me-2"></i><?= __('All Schedules') ?>
	</a>
	<a class="nav-link text-white<?= $controller === 'SchedulerRows' && $action === 'add' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'SchedulerRows', 'action' => 'add']) ?>">
		<i class="fas fa-plus me-2"></i><?= __('Add Schedule') ?>
	</a>

	<div class="px-3 mt-3 mb-2">
		<small class="text-white-50 text-uppercase fw-semibold"><?= __('Reference') ?></small>
	</div>
	<a class="nav-link text-white<?= $controller === 'QueueScheduler' && $action === 'intervals' ? ' bg-primary rounded-0' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'intervals']) ?>">
		<i class="fas fa-clock me-2"></i><?= __('Intervals Help') ?>
	</a>

	<?php if (\Cake\Core\Plugin::isLoaded('Queue')): ?>
	<div class="px-3 mt-3 mb-2">
		<small class="text-white-50 text-uppercase fw-semibold"><?= __('Queue Plugin') ?></small>
	</div>
	<a class="nav-link text-white" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'index']) ?>">
		<i class="fas fa-layer-group me-2"></i><?= __('Queue Dashboard') ?>
	</a>
	<a class="nav-link text-white" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'QueuedJobs', 'action' => 'index']) ?>">
		<i class="fas fa-tasks me-2"></i><?= __('Queued Jobs') ?>
	</a>
	<?php endif; ?>
</nav>
