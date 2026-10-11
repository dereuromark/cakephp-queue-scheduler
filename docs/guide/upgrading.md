# Upgrading

Update your Composer dependencies, then run both plugins' migrations as shown in [Installation](./installation). Back up the database before production migrations and check existing enabled rows before restarting cron.

Non-dry-run single-pass scheduler runs now use the same lock as loop mode. The cron user must be able to create and write the lock file; check its ownership and permissions, or configure a writable `QueueScheduler.lockPath`. A lock timeout exits unsuccessfully without dispatching; monitor the command's exit status. Invalid `--limit` values are rejected instead of being converted to unlimited dispatch.

Standalone mode now bypasses the host AppController's `beforeFilter()` as well as its initialization. Review your `adminAccess` gate and middleware before enabling it. Normal mode preserves a parent callback's redirect Response. Exceptions raised by the access gate propagate to your application.

The `cake6` development branch requires PHP 8.5, CakePHP 6 and the Queue and Tools `cake6` branches. Follow [Installation](./installation) for application Composer stability and development dependencies. Custom commands use parameterless `execute()` with inherited `$this->args` and `$this->io` properties. Controller callbacks return `void` and preserve parent redirects through the event result. Entity fields use CakePHP 6's `$patchable` property. Tests use PHPUnit 13.

Cake-command parameters must be JSON arrays of strings. Quote numeric arguments, such as `["42"]`; nested arrays, objects, booleans, and null values are rejected. Command output is retained when execution throws an exception.
