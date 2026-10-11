# Upgrading

Update your Composer dependencies, then run both plugins' migrations as shown in [Installation](./installation). Back up the database before production migrations and check existing enabled rows before restarting cron.

Non-dry-run single-pass scheduler runs now use the same lock as loop mode. The cron user must be able to create and write the lock file; check its ownership and permissions, or configure a writable `QueueScheduler.lockPath`. A lock timeout exits unsuccessfully without dispatching; monitor the command's exit status. Invalid `--limit` values are rejected instead of being converted to unlimited dispatch.

Standalone mode now bypasses the host AppController's `beforeFilter()` as well as its initialization. Review your `adminAccess` gate and middleware before enabling it. Normal mode preserves a parent callback's redirect Response. Exceptions raised by the access gate propagate to your application.

CakePHP 6 support is not declared. Keep current CakePHP 5 dependencies and plugins up to date, resolve application deprecations, and check this package's Composer constraints before a major upgrade.

Cake-command parameters must be JSON arrays of strings. Quote numeric arguments, such as `["42"]`; nested arrays, objects, booleans, and null values are rejected. Command output is retained when execution throws an exception.
