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

### Scheduling Cake Commands

### Scheduling Shell Commands
For security reasons executing raw shell commands is only enabled by default for debug mode.
Here you can add any shell command to be executed inside a Queue job.

## Schedule Frequency Options


## Configuration

For details see config/app.example.php file.


## Credit where credit is due
This plugin is heavily inspired by [LordSimal Scheduler plugin](https://github.com/LordSimal/cakephp-scheduler)
and [Laravel Task Scheduling Feature](https://laravel.com/docs/10.x/scheduling).
