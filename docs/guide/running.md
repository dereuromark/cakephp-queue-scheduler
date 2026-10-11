# Running the scheduler

## Single dispatcher

Run one dispatcher against each scheduler database. Workers can run on multiple hosts. The default file lock protects overlapping single-pass and loop runs on the same host; it does not coordinate different hosts.

For multiple application hosts, choose one cron host. For failover, implement `QueueScheduler\Scheduler\Lock\LockInterface` using a shared lock and override `RunCommand::createLock()` in your command subclass. Disable the plugin's console hook when loading it (`$this->addPlugin('QueueScheduler', ['console' => false])`), then register your subclass as `scheduler run` in your application's `console()` hook. Otherwise the plugin command registration overwrites yours. Keep a single designated dispatcher unless your shared locking strategy has been tested.

Dry-run mode skips the lock in both run modes and makes no scheduling writes.

The default lock path is `tmp/queue_scheduler.lock`, with a 30-second acquire timeout. Change it through `QueueScheduler.lockPath` and `QueueScheduler.lockAcquireTimeout`.

## Command flags

`bin/cake scheduler run` accepts:

- `--dry-run`: list events that would be dispatched without enqueueing them or
  updating `last_run`. Useful for diagnosing "why is X not firing yet" or
  smoke-testing a freshly added row.
- `--limit=N` (alias `-l N`): cap the number of events dispatched on this
  tick. The remainder stays due for the next run. Helps drain a backlog
  gracefully after downtime instead of flooding the queue all at once.
- `--duration=N|auto`: enable loop mode. Either an integer of seconds the
  command should keep scheduling, or the literal `auto` to fill the time
  until just before the next minute boundary. Requires `--interval`.
- `--interval=N`: in loop mode, the seconds to sleep between scheduling
  passes. The smallest practical row frequency. Requires `--duration`.

## Sub-minute scheduling

Cron's minimum granularity is one minute. To run rows at sub-minute
frequencies (e.g. `+10 seconds`, `PT5S`), use loop mode:

```text
* * * * * cd /path-to-your-project && bin/cake scheduler run --duration=auto --interval=10 >> /dev/null 2>&1
```

Each cron tick launches a process that loops `schedule()` calls every 10
seconds until just before the next minute, then exits. A file lock at
`tmp/queue_scheduler.lock` (override with `Configure::write('QueueScheduler.lockPath', ...)`)
prevents two processes from overlapping. If a slow iteration overruns the
boundary, the next cron-launched process blocks on the lock for up to 30
seconds and picks up where the previous left off; if the timeout expires, that process exits with an error. Check logs and the health indicator for missed ticks.

`--interval` is the global floor, not a per-row property: a row with
`+5 seconds` frequency and `--interval=10` fires every 10s, not every 5s.
Set `--interval` to the finest granularity any of your rows needs.

The single-runner rule from the [Single dispatcher](#single-dispatcher) section applies here too: one cron entry against one database, even in loop mode. The lock guards same-host overlap; cross-host coordination still requires swapping `FileLock` for a DB advisory lock or Redis backend.

The command exits with a non-zero status if any row threw while being
scheduled (a row being held back because a previous run is still queued
is **not** counted as a failure).


## Health indicator

A completed non-dry-run scheduling pass writes `QueueScheduler.lastTick` to the configured cache. The dashboard reports healthy when this timestamp is recent. A pass can record a heartbeat even if individual rows failed; check its exit status and logs as well. The indicator does not prove that workers are executing queued jobs.

`QueueScheduler.cacheConfig` defaults to `default`. Use a shared cache when the web server and dispatcher run on different hosts. `QueueScheduler.healthyWithinSeconds` defaults to 65; increase it when your cron interval is longer. Cache read failures display never-run; write failures are logged without failing dispatch.
