<?php

use Templating\View\Icon\BootstrapIcon;

return [
	'QueueScheduler' => [
		// Additional plugins that are not loaded, but should be included, use `-` prefix to exclude
		'plugins' => [],
		'allowRaw' => false, // By default, this is only enabled in debug mode for security reasons.
		'crontabMinimum' => '1 minute', // By default, this should be run as `* * * * *`

		// Admin Layout configuration:
		// - null (default): Uses 'QueueScheduler.queue_scheduler' isolated Bootstrap 5 layout
		// - false: Disables plugin layout, uses app's default layout
		// - string: Uses specified layout (e.g., 'QueueScheduler.queue_scheduler' or custom)
		'adminLayout' => null,

		// Standalone mode:
		// - false (default): Extends App\Controller\AppController (inherits app auth, components, config)
		// - true: Isolated admin that doesn't depend on the host app
		'standalone' => false,

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
