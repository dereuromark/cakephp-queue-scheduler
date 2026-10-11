# Manual runs and overrides

The row detail page offers two "Run Now" actions for Queue task rows:

- **Run Now**: fires the job exactly as configured: uses the row's stored `param` and `job_config`, advances `last_run` / `next_run`, and behaves identically to a normal cron tick.
- **Run with overrides...**: opens a collapsible form with two JSON textareas (`param` override and `job_config` override). Submitting it dispatches the job once with the override values, but **does not touch `last_run` / `next_run`**, so the row keeps firing on its regular cadence.

This is intended for incident response: for example, re-running yesterday's batch with a different date range, or running a single tenant out-of-band without skipping the next scheduled run. Override dispatches still respect the row's `allow_concurrent` flag: if a job for this reference is already queued and the row disallows concurrency, the override is rejected and no extra job is enqueued.

Each override dispatch is logged via Cake's `Log::write('info', ...)` against the default log channel, including the row id, the queued job id, the triggering identity (when available), and a truncated copy of the payload + config that were sent. Filter or route this channel separately if you want a dedicated audit trail.

Programmatic equivalent (use this from a controller or service, not from a Task):

```php
$ok = $this->SchedulerRows->runOnce($row, [
    'job_data' => ['tenant_id' => 42, 'date_from' => '2024-01-01'],
    'job_config' => ['priority' => 1],
    'triggered_by' => 'oncall-rerun',
]);
```

All three keys are optional. Omitting `job_data` reuses the row's stored param; omitting `job_config` reuses the row's stored config; a partial `job_config` (e.g. only `priority`) is merged on top of the stored config rather than replacing it wholesale.


Avoid simultaneous manual submissions and dispatcher runs for the same row. The queued-job check rejects an existing job, but does not provide a cross-process mutex for concurrent override requests. Payloads are included in the audit log; do not put credentials into overrides.
