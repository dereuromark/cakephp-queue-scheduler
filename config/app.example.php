<?php

use Templating\View\Icon\BootstrapIcon;

return [
	'QueueScheduler' => [
		// Additional plugins that are not loaded, but should be included, use `-` prefix to exclude
		'plugins' => [],
		'allowRaw' => false, // By default, this is only enabled in debug mode for security reasons.

		// Admin Layout configuration:
		// - null (default): Uses 'QueueScheduler.queue_scheduler' isolated Bootstrap 5 layout
		// - false: Disables plugin layout, uses app's default layout
		// - string: Uses specified layout (e.g., 'QueueScheduler.queue_scheduler' or custom)
		'adminLayout' => null,

		// Standalone mode:
		// - false (default): Extends App\Controller\AppController (inherits app auth, components, config)
		// - true: Isolated admin that doesn't depend on the host app
		'standalone' => false,

		// Admin access gate. REQUIRED — the host app MUST set this to a Closure
		// that returns true to grant access to /admin/queue-scheduler/...; anything
		// else (unset, non-Closure, returns false, returns a truthy non-bool, or
		// throws) yields a 403. The plugin can configure arbitrary scheduled
		// command execution; accidental exposure is harmful, so the default
		// policy is deny. Independent of `standalone` — runs in both modes.
		// Example — admin role check on the cakephp/authentication identity:
		'adminAccess' => function (\Cake\Http\ServerRequest $request): bool {
			$identity = $request->getAttribute('identity');

			return $identity !== null && in_array('admin', (array)$identity->roles, true);
		},

		// Auto-refresh dashboard (in seconds, 0 = disabled)
		'dashboardAutoRefresh' => 0,
	],
	// Icon configuration for the backend UI (optional, but recommended for better UX)
	// Without this, the UI will use Font Awesome icons from CDN when using the standalone layout.
	'Icon' => [
		'sets' => [
			'bs' => BootstrapIcon::class,
		],
	],
];
