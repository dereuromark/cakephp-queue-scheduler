# Installation

Run Composer from your application's root:

```bash
composer require dereuromark/cakephp-queue-scheduler
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
composer require cakephp/migrations
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

See CakePHP's [plugin loading](https://book.cakephp.org/5/en/plugins.html#loading-a-plugin) and [Migrations documentation](https://book.cakephp.org/migrations/) for application setup details.
