# QueueScheduler documentation

Dependencies:
- Queue plugin
- `dragonmantank/cron-expression` for working with crontab like expressions (import, export)

Using the GUI backend requires:
- Tools plugin

## Setup

Add the plugin to your Application class
```php
    $this->addPlugin('QueueScheduler');
```

Make sure to run the migrations command or manually set up your table:

    bin/cake migrations migrate -p QueueScheduler

If you have Auth/ACL activated, you might also want to add the backend controllers to
the role of your admin, so that those users have access to this backend.

## Scheduling

Add this in your crontab to run the scheduler every minute:
```
* * * * * cd /path-to-your-project && bin/cake scheduler run >> /dev/null 2>&1
```
Tip: Use `bin/cake scheduler run` without additional elements as basic command for local development/testing.

### Scheduling Queue Tasks

You can directly add Queue Tasks using FQCN.
```
Queue\Queue\Task\ExampleTask
```
### Scheduling Cake Commands

Adding CommandInterface classes also works using FQCN.
```
Cake\Command\SchemacacheBuildCommand
```

### Scheduling Shell Commands
For security reasons executing raw shell commands is only enabled by default for debug mode.
Here you can add any shell command to be executed inside a Queue job.
```
sh /some/shell.sh
```

## Schedule Frequency Options

You can use different styles depending on your use case.

### Crontab style
For larger time frames (e.g. months) or more complex scheduling (e.g. "every Tuesday at ...") this style is recommended.
See https://crontab.guru/ for details.

### DateInterval style

They either start with a `P` or a `+`. Other values are invalid.

`P1D` or `+ 1 day` mean the same thing.

You can also define more complex intervals by chaining: `+ 1 hour + 5 minutes`.

See https://www.php.net/manual/en/dateinterval.createfromdatestring.php for details.


## Configuration

For details see config/app.example.php file.


## Credit where credit is due
This plugin is heavily inspired by [LordSimal Scheduler plugin](https://github.com/LordSimal/cakephp-scheduler)
and [Laravel Task Scheduling Feature](https://laravel.com/docs/10.x/scheduling).
