# Upgrading

Update your Composer dependencies, then run both plugins' migrations as shown in [Installation](./installation). Back up the database before production migrations and check existing enabled rows before restarting cron.

Single-pass scheduler runs now use the same lock as loop mode. A lock timeout exits unsuccessfully without dispatching; monitor the command's exit status. Invalid `--limit` values are rejected instead of being converted to unlimited dispatch.

Standalone mode now bypasses the host AppController's `beforeFilter()` as well as its initialization. Review your `adminAccess` gate and middleware before enabling it. Normal mode preserves a parent callback's redirect Response. Exceptions raised by the access gate deny access with 403.

CakePHP 6 support is not declared. Keep current CakePHP 5 dependencies and plugins up to date, resolve application deprecations, and check this package's Composer constraints before a major upgrade.
