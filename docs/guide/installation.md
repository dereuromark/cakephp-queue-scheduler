# Installation

This development branch requires PHP 8.5 and CakePHP 6. Before installing, allow development dependencies in your application's root `composer.json` and prefer stable versions where available:

```json
{
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

Keep your existing application configuration and dependencies. Then run Composer from the application's root:

```bash
composer require cakephp/cakephp:^6.0@dev dereuromark/cakephp-queue-scheduler:dev-cake6
```

Composer resolves the supported PHP, CakePHP, Queue, and Tools versions from the package constraints. QueueScheduler uses the community [Queue plugin](https://dereuromark.github.io/cakephp-queue/), not CakePHP's separate queue package.

Load the plugins once, either through `config/plugins.php` or the following commands. Keep existing registrations:

```bash
bin/cake plugin load Tools
bin/cake plugin load Queue
bin/cake plugin load QueueScheduler
```

The database setup uses CakePHP Migrations. If your app does not already have it, install and load it:

```bash
composer require cakephp/migrations:dev-fix/6.x-cakephp-compatibility
bin/cake plugin load Migrations
```

Configure your application's database connection, then apply both plugins' migrations:

```bash
bin/cake migrations migrate -p Queue
bin/cake migrations migrate -p QueueScheduler
```

Use the same database connection for the scheduler rows and queued jobs. The dispatcher uses database transactions that cannot cover writes to separate connections.

The plugin registers `/admin/queue-scheduler` and `scheduler run`; additional routes are unnecessary. A newly installed admin returns 403 until you configure its access gate. Continue with [Getting started](./getting-started).

For optional icons and readable cron descriptions, see [Configuration](./configuration). The example configuration is a reference; the plugin does not load it automatically.

See CakePHP's [plugin loading](https://book.cakephp.org/6.x/plugins.html#loading-a-plugin) and [Migrations documentation](https://book.cakephp.org/migrations/) for application setup details.

The temporary Migrations branch tracks [upstream CakePHP 6 compatibility work](https://github.com/cakephp/migrations/pull/1121). Replace that branch constraint with the compatible release when it becomes available.
