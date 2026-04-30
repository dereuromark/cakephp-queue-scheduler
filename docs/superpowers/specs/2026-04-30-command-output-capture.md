# Command output capture — status, target, plan

> Working doc. Replaces the deleted `README_LOGGING.md` and supersedes the earlier draft of this spec (which proposed a redundant `last_output` column on `scheduler_rows`).

## Status quo

**All three row types already funnel through the queue worker.**

`SchedulerRow::_getJobTask()` routes by type:

| Row type | Job task | Owner |
|---|---|---|
| `TYPE_QUEUE_TASK` | the user's task class (e.g. `Queue.Example`) | host app |
| `TYPE_CAKE_COMMAND` | `QueueScheduler.CommandExecute` | this plugin |
| `TYPE_SHELL_COMMAND` | `Queue.Execute` | queue plugin |

The scheduler's `RunCommand` only inserts a row into `queued_jobs` with the right `job_task` and `data`; the queue worker (`Queue\Queue\Processor`) picks it up later and runs it. So whatever output capture the worker does applies uniformly to all three.

**The queue plugin ships output capture for every task.**

`vendor/dereuromark/cakephp-queue/src/Queue/Processor.php:runJob()` (lines ~265-345) does, for every job:

1. Dispatches `Queue.Job.started` event.
2. Calls `$this->io->enableOutputCapture($maxOutputSize)` if `Configure::read('Queue.captureOutput')` is true (default: not enabled). `$maxOutputSize` defaults to `Queue.maxOutputSize` (default 65536 bytes).
3. Invokes `$task->run((array)$data, $queuedJob->id)`.
4. Calls `$this->io->getOutputAsText($maxOutputSize)`, then `disableOutputCapture()`.
5. Persists the captured text via:
   - `QueuedJobs::markJobDone($job, $output)` on success → writes `queued_jobs.output`.
   - `QueuedJobs::markJobFailed($job, $failureMessage, $output)` on failure → writes `queued_jobs.failure_message` AND `queued_jobs.output`.
6. Dispatches `Queue.Job.completed` or `Queue.Job.failed`.

The `queued_jobs.output` column was added in queue plugin **v8.9.0 (2026-01-29)**. This plugin's `composer.json` already requires `^8.9.0`, so every supported install has the column.

**Our plugin already wires the back-pointer.**

`SchedulerRowsTable::run()` (line 387) writes the dispatched job's id to `scheduler_rows.last_queued_job_id` on every successful enqueue. So from a `SchedulerRow`, we can find "the queued job that this row's last run produced" via that single foreign key — no joins through reference strings, no scanning.

**What does not yet exist:**

- `SchedulerRowsTable` has no `belongsTo` association for `last_queued_job_id`. Today the controller would have to fetch the queued job manually.
- The admin row view (`templates/Admin/SchedulerRows/view.php`) does not render output or failure messages. Operators currently can only see whether a row was last run, not what it printed.
- No mention of `Queue.captureOutput` in the plugin's docs. Hosts that don't already enable it for the queue plugin won't see captured output either.

**What was abandoned (PR #9):** a parallel `command_logs` table, `LoggingConsoleIo` reflection-into-private-fields, 21-key config — all reinventing what the queue plugin already provides. Self-closed unmerged. Do not resurrect.

## Target

**Single user value:** "When I open a scheduled row in the admin, I see what its most recent run printed (stdout/stderr) and any failure message."

**Concrete success criteria:**

1. Admin row view (`/admin/queue-scheduler/scheduler-rows/view/{id}`) shows the last run's captured output and (if applicable) failure message.
2. The feature works uniformly for all three row types — Queue Task, Cake Command, Shell Command — because the capture happens at the queue-worker layer, not per-task.
3. Hosts opt in once, plugin-wide, by setting `Configure::write('Queue.captureOutput', true)`. No per-command instrumentation, no trait, no per-row config.
4. Zero new tables, zero new columns on tables this plugin owns.

**Explicitly out of scope:**

- Historical archive of every prior run beyond what the queue plugin already retains in `queued_jobs`.
- Alerting / monitoring thresholds / error-pattern matching.
- ANSI-stripping / truncate-vs-compress modes / per-command include-exclude lists.
- File-based logs with rotation. (Hosts that want this configure their own `Cake\Log` writer; not our concern.)
- A custom `ConsoleIo` subclass or a logging trait. The queue plugin's `Queue\Console\Io` already wraps the worker's IO with capture support.

## How to get there

### 1. Add the `belongsTo` association (the unlock)

In `SchedulerRowsTable::initialize()`:

```php
$this->belongsTo('LastQueuedJob', [
    'className' => 'Queue.QueuedJobs',
    'foreignKey' => 'last_queued_job_id',
    'propertyName' => 'last_queued_job',
]);
```

Add the property to `SchedulerRow`'s docblock so static analysis knows about it:

```
 * @property \Queue\Model\Entity\QueuedJob|null $last_queued_job
```

After this, controllers can do:

```php
$row = $this->SchedulerRows->get($id, ['contain' => ['LastQueuedJob']]);
// $row->last_queued_job->output            -- captured stdout+stderr (string|null)
// $row->last_queued_job->failure_message   -- string|null
// $row->last_queued_job->completed         -- DateTime|null
// $row->last_queued_job->status            -- queue status enum
```

### 2. Render in the admin row view

`templates/Admin/SchedulerRows/view.php` — add a section after the existing fields:

```php
<?php if ($schedulerRow->last_queued_job) : ?>
    <h3><?= __('Last run') ?></h3>
    <?php if ($schedulerRow->last_queued_job->failure_message) : ?>
        <details open>
            <summary><?= __('Failure message') ?></summary>
            <pre><?= h($schedulerRow->last_queued_job->failure_message) ?></pre>
        </details>
    <?php endif; ?>
    <?php if ($schedulerRow->last_queued_job->output) : ?>
        <details>
            <summary><?= __('Captured output') ?></summary>
            <pre><?= h($schedulerRow->last_queued_job->output) ?></pre>
        </details>
    <?php else : ?>
        <p class="text-muted"><?= __('No output captured. Set <code>Queue.captureOutput</code> to true to enable.') ?></p>
    <?php endif; ?>
<?php endif; ?>
```

### 3. Wire `contain` in the existing view action

`SchedulerRowsController::view()` — extend the existing `get()` call to contain `LastQueuedJob`. (Probably one-line change; verify by reading the current method.)

### 4. Document the toggle

`docs/README.md` — new short subsection under Configuration:

> ### Capturing scheduled-command output
>
> The plugin runs all scheduled tasks through the queue worker. To preserve their stdout/stderr for review in the admin, enable the queue plugin's built-in capture:
>
> ```php
> Configure::write('Queue.captureOutput', true);
> Configure::write('Queue.maxOutputSize', 65536); // bytes; default
> ```
>
> When enabled, captured text is persisted to `queued_jobs.output` for every job (success or failure) and shown on the row's view page in the admin. Without this flag, the row view shows only the failure message — successful runs leave no trace.

### 5. Tests

Two integration tests on the row view controller:

- One row with a successful queued job whose `output` is non-null → assert the response body contains the captured text.
- One row with a failed queued job whose `failure_message` is non-null → assert the response body contains the failure message.

Use `FixtureFactory::make()` for `QueuedJobs` (queue plugin ships factories) and the existing `SchedulerRowsFixtureFactory`. ~30 LOC total.

## Plan

| Step | File | Approx LOC |
|---|---|---|
| 1. Add `belongsTo('LastQueuedJob')` association | `src/Model/Table/SchedulerRowsTable.php` | 5 |
| 2. Add `@property` for IDE / phpstan | `src/Model/Entity/SchedulerRow.php` | 1 |
| 3. Add `contain` to the view action | `src/Controller/Admin/SchedulerRowsController.php` (or wherever `view()` lives) | 1 |
| 4. Render output + failure block | `templates/Admin/SchedulerRows/view.php` | ~15 |
| 5. Doc note | `docs/README.md` | ~10 |
| 6. Two integration tests | `tests/TestCase/Controller/Admin/SchedulerRowsControllerTest.php` | ~30 |

**Total: ~60 LOC across 6 files. No migration, no new column, no new task code, no event listeners, no trait, no `Cake\Log` reinvention.**

## Open questions

1. **Is there already a controller `view()` action?** I haven't read `src/Controller/Admin/SchedulerRowsController.php` yet. If it doesn't exist (admin uses `QueueSchedulerController::index()` only), the contain wiring goes in whichever action the row's "view" page is served from — needs a quick grep before estimating step 3.
2. **Should we tee captured output to `Cake\Log` as a separate scope?** Not in this PR. If a host wants file-rotated archival, they configure a writer against the `Queue.captureOutput` data themselves; we just expose the toggle. (Could be a follow-up PR if anyone asks for it.)
3. **What if the `last_queued_job_id` points at a row that the queue plugin has cleaned up?** `belongsTo` returns `null` for missing FKs. The view template's `if ($row->last_queued_job)` guard handles it gracefully — operator sees just the basic row info, no last-run section. No error.
4. **Confirmation copy when capture is off.** The "No output captured. Set `Queue.captureOutput` to true" hint is shown whenever `output` is null. That's a bit noisy on rows that never ran — maybe gate it on `last_queued_job->completed !== null` so it shows only when there *should* have been output. Minor; finalize during implementation.
