<?php

use Cake\Http\ServerRequest;
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
		'adminAccess' => function (ServerRequest $request): bool {
			$identity = $request->getAttribute('identity');

			return $identity !== null && in_array('admin', (array)$identity->roles, true);
		},

		// Auto-refresh dashboard (in seconds, 0 = disabled)
		'dashboardAutoRefresh' => 0,

		// Cache config that the scheduler heartbeat is written to and read
		// from. The admin index page shows a "Scheduler healthy / stale /
		// never run" pill based on this.
		// Defaults to 'default'. For multi-host deployments, point this at
		// a shared backend (Redis/Memcached) so the web tier can see a
		// heartbeat written by the CLI tier.
		//'cacheConfig' => 'default',

		// Maximum age (in seconds) of the heartbeat before the admin page
		// reports the scheduler as stale. Default 65: 60s for the cron
		// interval plus a few seconds of slack for pass duration and cron
		// jitter (the heartbeat is written at the *end* of a pass, not the
		// start). Raise it if you run cron less often than every minute.
		//'healthyWithinSeconds' => 65,
	],
	// Icon configuration for the backend UI (optional, but recommended for better UX)
	// Without this, the UI will use Font Awesome icons from CDN when using the standalone layout.
	'Icon' => [
		'sets' => [
			'bs' => BootstrapIcon::class,
		],
	],
];
