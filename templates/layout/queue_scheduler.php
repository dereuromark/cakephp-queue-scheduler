<?php
/**
 * QueueScheduler Admin Layout
 *
 * Self-contained admin layout using Bootstrap 5 and Font Awesome 6 via CDN.
 * Completely isolated from host application's CSS/JS.
 *
 * @var \Cake\View\View $this
 */

use Cake\Core\Configure;

$autoRefresh = 0;
$request = $this->getRequest();
if ($request && $request->getParam('controller') === 'QueueScheduler' && $request->getParam('action') === 'index') {
	$autoRefresh = (int)Configure::read('QueueScheduler.dashboardAutoRefresh') ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $this->fetch('title') ? strip_tags($this->fetch('title')) . ' - ' : '' ?>Queue Scheduler Admin</title>

	<!-- Bootstrap 5.3.3 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

	<!-- Font Awesome 6.7.2 -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous">

	<style>
		:root {
			--scheduler-primary: #6f42c1;
			--scheduler-success: #198754;
			--scheduler-warning: #ffc107;
			--scheduler-danger: #dc3545;
			--scheduler-info: #0dcaf0;
			--scheduler-secondary: #6c757d;
			--scheduler-dark: #212529;
			--scheduler-light: #f8f9fa;
			--scheduler-sidebar-bg: linear-gradient(135deg, #4a2c6a 0%, #2d1a42 100%);
			--scheduler-sidebar-width: 260px;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			background-color: #f4f6f9;
			min-height: 100vh;
		}

		/* Navbar */
		.scheduler-navbar {
			background: var(--scheduler-dark);
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.scheduler-navbar .navbar-brand {
			font-weight: 600;
			color: #fff;
		}

		.scheduler-navbar .navbar-brand i {
			color: var(--scheduler-primary);
		}

		/* Sidebar */
		.scheduler-sidebar {
			background: var(--scheduler-sidebar-bg);
			min-height: calc(100vh - 56px);
			width: var(--scheduler-sidebar-width);
			position: fixed;
			left: 0;
			top: 56px;
			padding: 1.5rem 0;
			overflow-y: auto;
		}

		.scheduler-sidebar .nav-section {
			padding: 0 1rem;
			margin-bottom: 1.5rem;
		}

		.scheduler-sidebar .nav-section-title {
			color: rgba(255,255,255,0.5);
			font-size: 0.75rem;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			padding: 0 0.75rem;
			margin-bottom: 0.5rem;
		}

		.scheduler-sidebar .nav-link {
			color: rgba(255,255,255,0.8);
			padding: 0.6rem 0.75rem;
			border-radius: 0.375rem;
			margin-bottom: 0.25rem;
			transition: all 0.2s ease;
		}

		.scheduler-sidebar .nav-link:hover {
			color: #fff;
			background: rgba(255,255,255,0.1);
		}

		.scheduler-sidebar .nav-link.active {
			color: #fff;
			background: var(--scheduler-primary);
		}

		.scheduler-sidebar .nav-link i {
			width: 1.25rem;
			margin-right: 0.5rem;
		}

		/* Main Content */
		.scheduler-main {
			margin-left: var(--scheduler-sidebar-width);
			padding: 1.5rem;
			min-height: calc(100vh - 56px);
		}

		/* Stats Cards */
		.stats-card {
			border: none;
			border-radius: 0.5rem;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			overflow: hidden;
		}

		.stats-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
		}

		.stats-card .card-body {
			padding: 1.25rem;
		}

		.stats-card .stats-icon {
			width: 48px;
			height: 48px;
			border-radius: 0.5rem;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.25rem;
		}

		.stats-card .stats-value {
			font-size: 1.75rem;
			font-weight: 700;
			line-height: 1.2;
		}

		.stats-card .stats-label {
			color: var(--scheduler-secondary);
			font-size: 0.875rem;
		}

		/* Status Badges */
		.badge-enabled {
			background-color: var(--scheduler-success);
		}

		.badge-disabled {
			background-color: var(--scheduler-secondary);
		}

		.badge-running {
			background-color: var(--scheduler-primary);
		}

		.badge-queued {
			background-color: var(--scheduler-warning);
			color: #000;
		}

		.badge-failed {
			background-color: var(--scheduler-danger);
		}

		/* Tables */
		.scheduler-table {
			background: #fff;
			border-radius: 0.5rem;
			overflow: hidden;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
		}

		.scheduler-table thead th {
			background: var(--scheduler-light);
			border-bottom: 2px solid #dee2e6;
			font-weight: 600;
			font-size: 0.875rem;
			text-transform: uppercase;
			letter-spacing: 0.025em;
			color: var(--scheduler-secondary);
		}

		.scheduler-table tbody tr:hover {
			background-color: rgba(111, 66, 193, 0.04);
		}

		/* Action Buttons */
		.btn-action {
			padding: 0.25rem 0.5rem;
			font-size: 0.875rem;
		}

		/* Flash Messages */
		.scheduler-flash {
			margin-bottom: 1rem;
		}

		/* Footer */
		.scheduler-footer {
			margin-left: var(--scheduler-sidebar-width);
			padding: 1rem 1.5rem;
			background: #fff;
			border-top: 1px solid #dee2e6;
			color: var(--scheduler-secondary);
			font-size: 0.875rem;
		}

		/* Responsive */
		@media (max-width: 991.98px) {
			.scheduler-sidebar {
				position: relative;
				width: 100%;
				min-height: auto;
				top: 0;
			}

			.scheduler-main {
				margin-left: 0;
			}

			.scheduler-footer {
				margin-left: 0;
			}
		}

		/* Yes/No Badges */
		.yes-no {
			display: inline-flex;
			align-items: center;
			padding: 0.25em 0.5em;
			font-size: 0.75rem;
			font-weight: 500;
			border-radius: 0.25rem;
		}

		.yes-no-yes {
			background-color: #d1e7dd;
			color: #0f5132;
		}

		.yes-no-no {
			background-color: #f8d7da;
			color: #842029;
		}

		/* Cards */
		.card {
			border: none;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			border-radius: 0.5rem;
		}

		.card-header {
			background: var(--scheduler-light);
			border-bottom: 1px solid #dee2e6;
			font-weight: 600;
		}

		/* Code blocks */
		code {
			background: #f1f3f4;
			padding: 0.125rem 0.375rem;
			border-radius: 0.25rem;
			font-size: 0.875em;
		}

		pre {
			background: #f8f9fa;
			padding: 1rem;
			border-radius: 0.375rem;
			border: 1px solid #dee2e6;
			overflow-x: auto;
		}

		pre code {
			background: transparent;
			padding: 0;
		}

		/* Collapsible sections */
		.collapse-icon {
			transition: transform 0.2s ease;
		}

		[aria-expanded="true"] .collapse-icon {
			transform: rotate(90deg);
		}

		/* Alert styles for running jobs */
		.alert-job-running {
			background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
			border: 1px solid #ffc107;
		}

		/* Schedule next run indicator */
		.next-run-soon {
			color: var(--scheduler-success);
			font-weight: 500;
		}

		.next-run-canceled {
			text-decoration: line-through;
			color: var(--scheduler-secondary);
		}
	</style>

	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
</head>
<body>
	<!-- Navbar -->
	<nav class="navbar navbar-expand-lg navbar-dark scheduler-navbar">
		<div class="container-fluid">
			<a class="navbar-brand" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'index']) ?>">
				<i class="fas fa-calendar-alt me-2"></i>Queue Scheduler
			</a>
			<!-- Mobile menu button -->
			<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav ms-auto">
					<?php if (\Cake\Core\Plugin::isLoaded('Queue')): ?>
					<li class="nav-item">
						<a class="nav-link" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'index']) ?>">
							<i class="fas fa-layer-group me-1"></i>Queue Dashboard
						</a>
					</li>
					<?php endif; ?>
					<li class="nav-item">
						<span class="nav-link text-light" title="<?= __('Server Time') ?>">
							<i class="far fa-clock me-1"></i>
							<?= date('Y-m-d H:i:s') ?>
						</span>
					</li>
				</ul>
			</div>
		</div>
	</nav>

	<!-- Mobile Offcanvas Navigation -->
	<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel" style="background: linear-gradient(135deg, #4a2c6a 0%, #2d1a42 100%);">
		<div class="offcanvas-header border-bottom border-secondary">
			<h5 class="offcanvas-title text-white" id="mobileNavLabel">
				<i class="fas fa-calendar-alt me-2"></i>Queue Scheduler
			</h5>
			<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body p-0">
			<?= $this->element('QueueScheduler.QueueScheduler/mobile_nav') ?>
		</div>
	</div>

	<div class="d-flex">
		<!-- Sidebar -->
		<?= $this->element('QueueScheduler.QueueScheduler/sidebar') ?>

		<!-- Main Content -->
		<main class="scheduler-main flex-grow-1">
			<!-- Flash Messages -->
			<div class="scheduler-flash">
				<?= $this->Flash->render() ?>
			</div>

			<?= $this->fetch('content') ?>
		</main>
	</div>

	<!-- Footer -->
	<footer class="scheduler-footer">
		<div class="d-flex justify-content-between align-items-center">
			<span>QueueScheduler Plugin for CakePHP</span>
			<span>
				<i class="fas fa-server me-1"></i>
				PHP <?= phpversion() ?>
			</span>
		</div>
	</footer>

	<?= $this->fetch('postLink') ?>

	<!-- Bootstrap 5.3.3 JS Bundle -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Initialize tooltips
			var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
			var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
				return new bootstrap.Tooltip(tooltipTriggerEl);
			});

			// Confirmation dialogs for postLink forms
			document.querySelectorAll('form[data-confirm-message]').forEach(function(form) {
				form.addEventListener('submit', function(e) {
					if (!confirm(this.dataset.confirmMessage)) {
						e.preventDefault();
					}
				});
			});

			<?php if ($autoRefresh > 0): ?>
			// Auto-refresh
			setTimeout(function() {
				window.location.reload();
			}, <?= $autoRefresh * 1000 ?>);
			<?php endif; ?>
		});
	</script>

	<?= $this->fetch('script') ?>
</body>
</html>
