<?php
/**
 * Sidebar navigation element for QueueScheduler admin.
 *
 * @var \Cake\View\View $this
 */

$request = $this->getRequest();
$controller = $request ? $request->getParam('controller') : '';
$action = $request ? $request->getParam('action') : '';
?>
<aside class="scheduler-sidebar d-none d-lg-block">
	<div class="nav-section">
		<div class="nav-section-title"><?= __d('queue_scheduler', 'Dashboard') ?></div>
		<nav class="nav flex-column">
			<a class="nav-link<?= $controller === 'QueueScheduler' && $action === 'index' ? ' active' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'index']) ?>">
				<i class="fas fa-tachometer-alt"></i><?= __d('queue_scheduler', 'Overview') ?>
			</a>
		</nav>
	</div>

	<div class="nav-section">
		<div class="nav-section-title"><?= __d('queue_scheduler', 'Scheduled Jobs') ?></div>
		<nav class="nav flex-column">
			<a class="nav-link<?= $controller === 'SchedulerRows' && $action === 'index' ? ' active' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'SchedulerRows', 'action' => 'index']) ?>">
				<i class="fas fa-list"></i><?= __d('queue_scheduler', 'All Schedules') ?>
			</a>
			<a class="nav-link<?= $controller === 'SchedulerRows' && $action === 'add' ? ' active' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'SchedulerRows', 'action' => 'add']) ?>">
				<i class="fas fa-plus"></i><?= __d('queue_scheduler', 'Add Schedule') ?>
			</a>
		</nav>
	</div>

	<div class="nav-section">
		<div class="nav-section-title"><?= __d('queue_scheduler', 'Reference') ?></div>
		<nav class="nav flex-column">
			<a class="nav-link<?= $controller === 'QueueScheduler' && $action === 'available' ? ' active' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'available']) ?>">
				<i class="fas fa-list"></i><?= __d('queue_scheduler', 'Commands & Tasks') ?>
			</a>
			<a class="nav-link<?= $controller === 'QueueScheduler' && $action === 'intervals' ? ' active' : '' ?>" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'intervals']) ?>">
				<i class="fas fa-clock"></i><?= __d('queue_scheduler', 'Intervals Help') ?>
			</a>
		</nav>
	</div>

	<?php if (\Cake\Core\Plugin::isLoaded('Queue')): ?>
	<div class="nav-section">
		<div class="nav-section-title"><?= __d('queue_scheduler', 'Queue Plugin') ?></div>
		<nav class="nav flex-column">
			<a class="nav-link" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'index']) ?>">
				<i class="fas fa-layer-group"></i><?= __d('queue_scheduler', 'Queue Dashboard') ?>
			</a>
			<a class="nav-link" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'QueuedJobs', 'action' => 'index']) ?>">
				<i class="fas fa-tasks"></i><?= __d('queue_scheduler', 'Queued Jobs') ?>
			</a>
		</nav>
	</div>
	<?php endif; ?>
</aside>
