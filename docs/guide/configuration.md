# Configuration

Set plugin options in your application's `config/app.php` or through `Configure::write()` in `config/bootstrap.php`. The package's `config/app.example.php` is a reference and is not loaded automatically.

| Option | Default | Purpose |
|---|---|---|
| `adminAccess` | Unset | Required closure granting admin access |
| `standalone` | `false` | Bypass the host AppController's initialization and beforeFilter |
| `adminLayout` | `null` | Use the isolated Bootstrap layout |
| `adminBackUrl` | Unset | Show a link from the admin header back to the application |
| `adminBackLabel` | Back to App | Label for that optional link |
| `dashboardAutoRefresh` | `0` | Dashboard refresh interval in seconds; zero disables it |
| `allowRaw` | `false` | Allow shell-command rows outside debug mode |
| `cacheConfig` | `default` | Cache for the scheduler heartbeat |
| `healthyWithinSeconds` | `65` | Maximum heartbeat age in seconds |
| `lockPath` | `TMP . 'queue_scheduler.lock'` | Same-host dispatcher lock |
| `lockAcquireTimeout` | `30` | Lock wait in seconds for every run mode |
| `maxConsecutiveFailures` | `0` | Reruns before disabling a repeatedly aborted row; zero is unlimited |

## Layout and navigation

`adminLayout = null` uses `QueueScheduler.queue_scheduler`, an isolated Bootstrap layout. `false` uses the application's default layout. A string selects a specific layout, such as `AdminTheme.admin`.

Set an application route array for the optional header link:

```php
use Cake\Core\Configure;

Configure::write('QueueScheduler.adminBackUrl', [
    'plugin' => false,
    'prefix' => 'Admin',
    'controller' => 'Overview',
    'action' => 'index',
]);
Configure::write('QueueScheduler.adminBackLabel', 'Back to admin');
```

Use a route that exists in your app. The plugin does not create an Overview controller.

## Optional icons and descriptions

The isolated layout includes a Font Awesome stylesheet and falls back to those icons. For a configured icon set, install the Templating plugin:

```bash
composer require dereuromark/cakephp-templating
bin/cake plugin load Templating
```

In `config/bootstrap.php`:

```php
use Cake\Core\Configure;
use Templating\View\Icon\BootstrapIcon;

Configure::write('Icon.sets.bs', BootstrapIcon::class);
```

Load the matching icon stylesheet in a custom application layout. The package's example configuration uses this optional Templating class; do not copy that part without installing it.

For readable cron descriptions, install the optional descriptor:

```bash
composer require panlatent/cron-expression-descriptor
```

Command discovery scans your application and loaded plugins. The old `QueueScheduler.plugins` example had no effect and has been removed; load a plugin to make its commands discoverable.

## Repeated failures

For non-concurrent rows, a previously aborted queue job is rerun in place. Jobs still retrying are held back. `maxConsecutiveFailures = 1` permits one rerun and disables the schedule after the next abort; zero permits unlimited reruns. A fresh dispatch or re-enabling a row resets the counter.

This requires a Queue release that records terminal `aborted` status; older supported releases leave the feature dormant. Register a `QueueScheduler.Row.disabled` listener on the scheduler table's event manager or CakePHP's global event manager. The event data contains `row` and `consecutiveFailures`.

For example, register this listener in `config/bootstrap.php`:

```php
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Log\Log;

EventManager::instance()->on('QueueScheduler.Row.disabled', function (EventInterface $event): void {
    $row = $event->getData('row');
    $failures = $event->getData('consecutiveFailures');
    Log::warning(sprintf('Schedule #%d disabled after %d reruns', $row->id, $failures));
});
```
