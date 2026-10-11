<?php

use Cake\Http\ServerRequest;
use Templating\View\Icon\BootstrapIcon;

return [
	'QueueScheduler' => [
		'allowRaw' => false, // By default, this is only enabled in debug mode for security reasons.

		// Admin Layout configuration:
		// - null (default): Uses 'QueueScheduler.queue_scheduler' isolated Bootstrap 5 layout
		// - false: Disables plugin layout, uses app's default layout
		// - string: Uses specified layout (e.g., 'QueueScheduler.queue_scheduler' or custom)
		'adminLayout' => null,

		// Back-to-App link in the admin header (opt-in). When set, an outline
		// button appears in the top navbar so admins can escape the
		// plugin-isolated layout. Accepts anything Router::url() takes: Cake
		// URL array, path string, or full URL. Use 'plugin' => false to
		// anchor the builder to the host app rather than the QueueScheduler plugin.
		// 'adminBackUrl' => ['plugin' => false, 'prefix' => 'Admin', 'controller' => 'Overview', 'action' => 'index'],
		// 'adminBackLabel' => 'Back to admin', // Optional. Defaults to "Back to App".

		// Standalone mode:
		// - false (default): Extends App\Controller\AppController (inherits app auth, components, config)
		// - true: Skips AppController initialize() and beforeFilter(); middleware still runs
		'standalone' => false,

		// Required admin access gate: set this to a Closure
		// that returns true to grant access to /admin/queue-scheduler/...; anything
		// else (unset, non-Closure, returns false, or returns a truthy non-bool)
		// yields a 403. Exceptions propagate to the application. The plugin can configure arbitrary scheduled
		// command execution; accidental exposure is harmful, so the default
		// policy is deny. This runs in both modes, independently of `standalone`.
		// Example: admin role check on the cakephp/authentication identity:
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

		// Maximum seconds the run command (single pass or loop mode)
		// waits to acquire the scheduler file lock before giving up and
		// exiting without scheduling. Prevents overlapping loop runs from
		// piling up. Default 30.
		//'lockAcquireTimeout' => 30,

		// Absolute path to the file lock used by all run modes to ensure a single
		// active scheduler process per host. Default is TMP . 'queue_scheduler.lock'
		// (e.g. tmp/queue_scheduler.lock). Note: this is a LOCAL file lock and does
		// NOT coordinate across hosts. Running loop mode on multiple hosts can still
		// produce overlapping schedules.
		//'lockPath' => TMP . 'queue_scheduler.lock',

		// Backoff for repeatedly-failing non-concurrent rows. When the row's
		// previously dispatched job terminally failed (queue status "aborted"),
		// the next tick reruns that same job in place instead of queuing a new
		// one, so a broken task does not pile up a failed job every interval.
		// After this many consecutive reruns (without an intervening success)
		// the row is disabled and a `QueueScheduler.Row.disabled` event is fired
		// so the host app can alert. Re-enabling a disabled row resets the count.
		// 0 (default) = unlimited reruns, never auto-disable. Requires the queue
		// to record terminal "aborted" state.
		//'maxConsecutiveFailures' => 0,
	],
	// Icon configuration for the backend UI (optional, but recommended for better UX)
	// Without this, the UI will use Font Awesome icons from CDN when using the standalone layout.
	'Icon' => [
		'sets' => [
			'bs' => BootstrapIcon::class,
		],
	],
];
