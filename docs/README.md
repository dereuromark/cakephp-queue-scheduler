# QueueScheduler documentation

Dependencies:
- Queue plugin
- `dragonmantank/cron-expression` for working with crontab like expressions (import, export)

Using the GUI backend requires:
- Tools plugin

## Setup

Load the plugin
```
bin/cake plugin load QueueScheduler
```

Make sure to run the migrations command or manually set up your table:

    bin/cake migrations migrate -p QueueScheduler

If you have Auth/ACL activated, you might also want to add the backend controllers to
the role of your admin, so that those users have access to this backend.

## Using the GUI

Once the plugin is loaded and migrations are run, you can access the GUI backend at:

```
/admin/queue-scheduler
```

The routes are automatically configured by the plugin. No additional routing setup is needed.

From the GUI you can:
- Add new scheduled tasks (Queue tasks, Cake commands, or shell commands)
- Edit existing schedules
- Enable/disable tasks
- Manually trigger tasks
- View task details and next run times

**Note:** If you have Auth/ACL activated, make sure your admin users have access to the `QueueScheduler` plugin controllers.

## Scheduling

Add this in your crontab to run the scheduler every minute:
```cronexp
* * * * * cd /path-to-your-project && bin/cake scheduler run >> /dev/null 2>&1
```
Tip: Use `bin/cake scheduler run` without additional elements as basic command for local development/testing.

### Command flags

`bin/cake scheduler run` accepts:

- `--dry-run` — list events that would be dispatched without enqueueing them or
  updating `last_run`. Useful for diagnosing "why is X not firing yet" or
  smoke-testing a freshly added row.
- `--limit=N` (alias `-l N`) — cap the number of events dispatched on this
  tick. The remainder stays due for the next run. Helps drain a backlog
  gracefully after downtime instead of flooding the queue all at once.
- `--duration=N|auto` — enable loop mode. Either an integer of seconds the
  command should keep scheduling, or the literal `auto` to fill the time
  until just before the next minute boundary. Requires `--interval`.
- `--interval=N` — in loop mode, the seconds to sleep between scheduling
  passes. The smallest practical row frequency. Requires `--duration`.

#### Sub-minute scheduling

Cron's minimum granularity is one minute. To run rows at sub-minute
frequencies (e.g. `+10 seconds`, `PT5S`), use loop mode:

```cronexp
* * * * * cd /path-to-your-project && bin/cake scheduler run --duration=auto --interval=10 >> /dev/null 2>&1
```

Each cron tick launches a process that loops `schedule()` calls every 10
seconds until just before the next minute, then exits. A file lock at
`tmp/queue_scheduler.lock` (override with `Configure::write('QueueScheduler.lockPath', ...)`)
prevents two processes from overlapping. If a slow iteration overruns the
boundary, the next cron-launched process blocks on the lock for up to 30
seconds and picks up where the previous left off — there is no coverage
gap for normal slowdowns.

`--interval` is the global floor, not a per-row property: a row with
`+5 seconds` frequency and `--interval=10` fires every 10s, not every 5s.
Set `--interval` to the finest granularity any of your rows needs.

**Multi-host caveat:** the default `FileLock` is single-host only. Two
app servers running this cron entry against the same database will each
hold their own local lock and double-schedule. If you run multiple
schedulers, implement `QueueScheduler\Scheduler\Lock\LockInterface` with
a DB advisory lock or Redis backend and inject via a custom subclass of
`RunCommand`.

The command exits with a non-zero status if any row threw while being
scheduled (a row being held back because a previous run is still queued
is **not** counted as a failure).

### Scheduling Queue Tasks

You can directly add Queue Tasks using `Plugin.Name` syntax or FQCN.
```
Queue.Example
// or
Queue\Queue\Task\ExampleTask
```

If you need to pass some payload data, you can use the param textarea for this using JSON:
```
{
    "dryRun": true,
    "id": 123,
    "key": "value"
}
```

### Scheduling Cake Commands

Adding CommandInterface classes also works using `Plugin.Name` syntax or FQCN:
```
MyPlugin.MyName
// or e.g.
Cake\Command\SchemacacheBuildCommand
```

If you need to pass additional args, you can use the param textarea for this using JSON:
```
[
    "-v",
    "--dry-run",
    "--some-option \"Some value\"",
]
```

### Scheduling Shell Commands
For security reasons executing raw shell commands is only enabled by default for debug mode.
Here you can add any shell command to be executed inside a Queue job.
```
sh /some/shell.sh
```

This type does not need the param textarea as all args are directly passed along the command here.

### Job Config (queue routing & priority)

The optional **Job Config** field accepts a JSON object that is merged into
the `QueuedJobsTable::createJob()` call. Allowed keys:

| Key | Type | Effect |
|---|---|---|
| `priority` | int 1-10 | Lower runs sooner. Default is 5. |
| `group` | string | Worker group; matches `bin/cake queue worker --group=...`. Lets you route scheduled jobs to a dedicated worker pool. |

Example: route a nightly cleanup to a low-priority batch worker:
```json
{"priority": 8, "group": "batch"}
```

Other `Queue\Config\JobConfig` keys (`notBefore`, `status`, `reference`) are
intentionally **not** accepted — `notBefore` is meaningless for cron-driven
dispatch (cron already controls timing), `reference` is set automatically
to `queue-scheduler-{id}`, and `status` is a runtime field overwritten on
the first progress tick. Unknown keys are rejected at save time so typos
like `prioirty` surface immediately instead of silently being ignored.

## Schedule Frequency Options

You can use different styles depending on your use case.

### Shortcuts
- `@yearly`
- `@annually`
- `@monthly`
- `@weekly`
- `@daily`
- `@hourly`
- `@minutely`

It calculates itself off the `created` datetime.

### Crontab style
For larger time frames (e.g. months) or more complex scheduling (e.g. "every Tuesday at ...") this style is recommended.
See https://crontab.guru/ for details.

```cronexp
*    *    *    *    *   /path/to/somecommand.sh
|    |    |    |    |            |
|    |    |    |    |    Command or Script to execute
|    |    |    |    |
|    |    |    | Day of week(0-6 | Sun-Sat)
|    |    |    |
|    |    |  Month(1-12)
|    |    |
|    |  Day of Month(1-31)
|    |
|   Hour(0-23)
|
Min(0-59)
```

E.g. "At 04:05" each day:
```cronexp
5 4 * * *
```

### DateInterval style

They either start with a `P` or a `+`. Other values are invalid.

- `P1D` and `+ 1 day` mean the same thing.
- `P2W` and `+ 2 weeks` mean the same thing.

You can also define more complex intervals by chaining: `+ 1 hour + 5 minutes`.

See https://www.php.net/manual/en/dateinterval.createfromdatestring.php for details.


## Configuration

You can configure the plugin further through
```php
'QueueScheduler' => [
    ...
],
```

in your app.php config.
For details see `config/app.example.php` file.

### Icons
The backend UI uses icons for better UX. To enable them, configure an icon set in your `config/app.php`:
```php
use Templating\View\Icon\BootstrapIcon;

'Icon' => [
    'sets' => [
        'bs' => BootstrapIcon::class,
    ],
],
```

Available icon sets from the Tools plugin:
- `BootstrapIcon` - Bootstrap Icons
- `FontAwesome4Icon`, `FontAwesome5Icon`, `FontAwesome6Icon` - Font Awesome
- `FeatherIcon` - Feather Icons
- `MaterialIcon` - Material Icons

Without icon configuration, the UI will fall back to text-based labels.

### Admin layout

`QueueScheduler.adminLayout` controls which layout the admin views render in:

- `null` (default) — uses the plugin's isolated `QueueScheduler.queue_scheduler` Bootstrap 5 layout. The admin works without depending on the host app's CSS/JS pipeline.
- `false` — disables the plugin layout entirely; views fall back to the host app's default layout. Use this when you want the admin to inherit your app chrome.
- `string` — a specific layout name, e.g. `'AdminTheme.admin'`, when you want to embed the admin in a custom theme.

This is independent of `QueueScheduler.standalone` (which controls whether the admin extends the host's `AppController`); see the Security section for that toggle.

### Dashboard auto-refresh

`QueueScheduler.dashboardAutoRefresh` (integer, seconds; default `0`) sets a meta-refresh interval on the admin dashboard so it polls itself for fresh state without manual reload. `0` disables auto-refresh; a typical value is `30` or `60`.

### Plugins
If you want to further include/exclude plugins, you can use the `plugins` key. Use `-` prefix to exclude.
```php
    'plugins' => [
        'Foo',
        '-ExcludeMe,
        ...
    ],
```

### Explaining crontab configuration

Often, the crontab style is not very human readable.
Install the following dependendy and it will translate for you:
```bash
composer require panlatent/cron-expression-descriptor
```


### Security

The scheduler admin backend can configure arbitrary scheduled command execution
(Cake commands, Queue tasks, and — when explicitly enabled — shell commands).
Treat the URL like SSH access: it must be locked down.

#### `QueueScheduler.adminAccess` (required, default-deny)

The plugin **fails closed** by default. The host application MUST set
`QueueScheduler.adminAccess` to a `Closure` that receives the current request
and returns literal `true` to grant access. Anything else — unset, non-Closure,
returns `false`, returns a truthy non-bool, or throws — yields a `403`.

```php
// In config/bootstrap.php (or wherever your plugin config lives):

// Example 1 — admin role check (cakephp/authentication identity):
Configure::write('QueueScheduler.adminAccess', function (\Cake\Http\ServerRequest $request): bool {
    $identity = $request->getAttribute('identity');
    return $identity !== null && in_array('admin', (array)$identity->roles, true);
});

// Example 2 — IP allow-list for a private staging environment:
Configure::write('QueueScheduler.adminAccess', function (\Cake\Http\ServerRequest $request): bool {
    return in_array($request->clientIp(), ['10.0.0.5', '10.0.0.6'], true);
});

// Example 3 — wide-open on local dev only (do NOT ship this to production):
if (Configure::read('debug')) {
    Configure::write('QueueScheduler.adminAccess', fn () => true);
}
```

The gate runs in `beforeFilter` for every admin controller in the plugin and
plays nicely with the cakephp/authorization plugin (it calls
`skipAuthorization()` so the policy layer doesn't double-reject).

This is independent of `QueueScheduler.standalone` — even in standalone mode
(where the host's `AppController` setup is bypassed), the access gate still
runs. Standalone mode is the "skip host auth components" axis;
`adminAccess` is the "who is allowed in" axis.

#### Shell command execution

`QueueScheduler.allowRaw` enables the `Shell Command` row type in production.
It is off by default and Shell rows are filtered out of `findActive()` unless
either `debug=true` or `allowRaw=true` is set. Only enable it on a secured,
contained environment — combined with a permissive `adminAccess` gate, raw
shell execution becomes RCE-as-a-feature.

