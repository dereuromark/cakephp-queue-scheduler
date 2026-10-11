# Getting started

Complete [Installation](./installation) first. This walkthrough schedules Queue's harmless example task, then executes it with a worker.

## Allow access to the admin

Add an access decision to `config/bootstrap.php`, after your application's configuration has loaded:

```php
use Cake\Core\Configure;
use Cake\Http\ServerRequest;

Configure::write('QueueScheduler.adminAccess', function (ServerRequest $request): bool {
    $identity = $request->getAttribute('identity');

    return $identity !== null && in_array('admin', (array)$identity->roles, true);
});
```

Adapt the role lookup to your identity's fields. Existing application authentication still applies. For an isolated local development application without login, use this temporary gate instead:

```php
if (Configure::read('debug')) {
    Configure::write('QueueScheduler.adminAccess', fn () => true);
}
```

This grants every caller access while debug is enabled. Keep it out of shared or production environments. See [Security](./security) for standalone mode and middleware.

## Create your first schedule

Open `/admin/queue-scheduler` and follow the link to add a row:

| Field | Value |
|---|---|
| Name | Example every minute |
| Type | Queue Task |
| Content | `Queue.Example` |
| Frequency | `+1 minute` |
| Param | Leave blank |
| Enabled | Checked |
| Allow concurrent | Unchecked |

An interval schedule with no previous run is due immediately. Cron schedules wait for their next matching time. Save the row, then inspect due events:

```bash
bin/cake scheduler run --dry-run
```

Dispatch them into the queue:

```bash
bin/cake scheduler run
```

The dispatcher creates queued jobs; it does not execute your task. Start a worker separately:

```bash
bin/cake queue run --max-runtime=10
```

Queue's example task prints a success message. Open the schedule's detail page to inspect its last queued job and next run. The worker can keep polling until its runtime expires; stop it with Ctrl+C during development.

## Keep it running

Run one dispatcher from cron and keep workers supervised. Add this cron entry on one designated host, using your actual project path:

```text* * * * * cd /path/to/application && bin/cake scheduler run >> /path/to/application/logs/scheduler.log 2>&1
```

Check [Running the scheduler](./running) before using multiple hosts or sub-minute intervals. Logs expose dispatch errors; workers have separate execution and retry settings in the Queue plugin.

## Troubleshooting

A 403 means the admin gate did not return literal `true`, or your application's auth denied access. A 404 usually means the plugin's routes were disabled or application routes intercepted the URL. A due schedule with no new job may be disabled, outside its time window, or held back by a previous queued job. Run `--dry-run`, inspect the row's next run, and check the worker and error logs.
