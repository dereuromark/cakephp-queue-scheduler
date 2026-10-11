# Contributing

Work from the repository root:

```bash
composer install
composer cs-check
vendor/bin/phpunit
composer stan-setup
composer stan
```

The test suite defaults to an in-memory SQLite database. Set `DB_URL` for MySQL or PostgreSQL runs. CI checks supported PHP versions and the lowest dependency set across the configured database matrix.

Build documentation from `docs/`:

```bash
npm ci
npm audit --audit-level=moderate
npm run docs:build
npm run docs:dev
```

Add regression coverage for dispatch, callbacks, and validation changes. Keep examples aligned with the commands and configuration in the current source. See the [repository contribution guide](https://github.com/dereuromark/cakephp-queue-scheduler/blob/master/.github/CONTRIBUTING.md) for pull requests.
