<?php

use Templating\View\Icon\BootstrapIcon;

return [
	'QueueScheduler' => [
		// Additional plugins that are not loaded, but should be included, use `-` prefix to exclude
		'plugins' => [],
		'allowRaw' => false, // By default, this is only enabled in debug mode for security reasons.
		'crontabMinimum' => '1 minute', // By default, this should be run as `* * * * *`
	],
	// Icon configuration for the backend UI (optional, but recommended for better UX)
	// Without this, the UI will fall back to text-based icons.
	'Icon' => [
		'sets' => [
			'bs' => BootstrapIcon::class,
		],
	],
];
