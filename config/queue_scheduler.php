<?php

/**
 * Queue Scheduler Logging Configuration
 *
 * Configure logging options for CLI command execution
 */
return [
	'QueueScheduler' => [
		/**
		 * Logging configuration for CLI runs
		 */
		'logging' => [
			// Enable/disable logging globally
			'enabled' => env('QUEUE_SCHEDULER_LOGGING_ENABLED', true),

			// Store logs in database
			'store_in_database' => env('QUEUE_SCHEDULER_LOG_TO_DB', true),

			// Store logs in files
			'store_in_file' => env('QUEUE_SCHEDULER_LOG_TO_FILE', true),

			// Path for log files (relative to app root)
			'file_path' => env('QUEUE_SCHEDULER_LOG_PATH', LOGS . 'scheduler' . DS),

			// Enable log file rotation
			'file_rotate' => env('QUEUE_SCHEDULER_LOG_ROTATE', true),

			// Maximum size per log file before rotation
			'file_max_size' => env('QUEUE_SCHEDULER_LOG_MAX_SIZE', '10MB'),

			// Maximum number of rotated log files to keep
			'file_max_files' => env('QUEUE_SCHEDULER_LOG_MAX_FILES', 10),

			// Include timestamp in log entries
			'include_timestamp' => true,

			// Include command arguments in logs
			'include_command_args' => true,

			// Log retention period in days (for database logs)
			'retention_days' => env('QUEUE_SCHEDULER_LOG_RETENTION_DAYS', 30),

			// Verbosity levels for different components
			'verbosity' => [
				// Log level for scheduler runs
				'scheduler' => env('QUEUE_SCHEDULER_LOG_VERBOSITY_SCHEDULER', 'normal'),

				// Log level for command execution
				'commands' => env('QUEUE_SCHEDULER_LOG_VERBOSITY_COMMANDS', 'normal'),

				// Log level for queue tasks
				'tasks' => env('QUEUE_SCHEDULER_LOG_VERBOSITY_TASKS', 'normal'),
			],

			// List of commands to exclude from logging
			'exclude_commands' => [
				// Add command class names here to exclude them from logging
				// e.g., 'App\Command\HealthCheckCommand',
			],

			// List of commands to always log (even if global logging is disabled)
			'force_log_commands' => [
				// Add command class names here to always log them
				// e.g., 'App\Command\CriticalTaskCommand',
			],
		],

		/**
		 * Performance monitoring
		 */
		'monitoring' => [
			// Track memory usage
			'track_memory' => env('QUEUE_SCHEDULER_TRACK_MEMORY', true),

			// Track execution time
			'track_time' => env('QUEUE_SCHEDULER_TRACK_TIME', true),

			// Alert thresholds
			'alerts' => [
				// Alert if execution time exceeds (seconds)
				'max_execution_time' => env('QUEUE_SCHEDULER_ALERT_MAX_TIME', 300),

				// Alert if memory usage exceeds
				'max_memory_usage' => env('QUEUE_SCHEDULER_ALERT_MAX_MEMORY', '128MB'),

				// Alert if stderr contains error patterns
				'error_patterns' => [
					'fatal',
					'emergency',
					'critical error',
					'exception',
				],
			],
		],

		/**
		 * Output formatting options
		 */
		'output' => [
			// Strip ANSI color codes from logs
			'strip_ansi' => env('QUEUE_SCHEDULER_STRIP_ANSI', false),

			// Maximum length for stdout/stderr in database (characters)
			'max_output_length' => env('QUEUE_SCHEDULER_MAX_OUTPUT_LENGTH', 65535),

			// Truncate or compress large outputs
			'handle_large_output' => env('QUEUE_SCHEDULER_HANDLE_LARGE_OUTPUT', 'truncate'), // 'truncate' or 'compress'
		],
	],
];
