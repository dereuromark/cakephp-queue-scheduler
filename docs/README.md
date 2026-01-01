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
Make sure that the admin backend (GUI) is secure through ACL and can only be reached by admins.

For security reasons you can by default only run Cake command and Queue types in non-debug mode.
Allowing any shell script can potentially be a security risk.
As such, only use `QueueScheduler.allowRaw` "raw command execution" on a secure and containered environment, e.g. staging.

