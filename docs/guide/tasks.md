# Task types and payloads

The three row types differ in what goes in **Content** and **Param**:

| Type | Content | Param | Param shape |
|---|---|---|---|
| Queue Task | `Plugin.Name` or FQCN ending in `Task` | optional payload | JSON object `{...}` |
| Cake Command | `Plugin.Name` or FQCN ending in `Command` | optional argv | JSON array `[...]` |
| Shell Command | full command line (e.g. `bin/cake foo --bar`) | **must be empty** | Not used |

**Empty param means "no payload / no args"** for Queue Task and Cake Command. Leave the field blank. The literals `[]` and `{}` (and whitespace variants like `[ ]` / `{\n}`) are accepted as a typo-friendly synonym for "blank" and normalized to an empty string at save time, so they do not trip the validators that otherwise reject empty collections.

## Scheduling Queue Tasks

You can directly add Queue Tasks using `Plugin.Name` syntax or FQCN.
```
Queue.Example
// or
Queue\Queue\Task\ExampleTask
```

If you need to pass some payload data, you can use the param textarea for this using JSON:
```json
{
    "dryRun": true,
    "id": 123,
    "key": "value"
}
```

## Scheduling Cake Commands

Use a `CommandInterface` class with a constructor that can be called without arguments. Commands requiring injected services are not constructed through the application container. Use `Plugin.Name` syntax or an FQCN:
```
MyPlugin.MyName
// or e.g.
Cake\Command\SchemacacheBuildCommand
```

If you need to pass additional args, you can use the param textarea for this using JSON. Each entry must be a string containing one argv token: the array is forwarded straight to `$command->run($args, $io)`, so use one entry per token rather than embedding shell-style quoting inside a single string:
```json
[
    "-v",
    "--dry-run",
    "--some-option=Some value"
]
```

If the command takes no arguments, leave the field blank rather than typing `[]`.

## Scheduling Shell Commands
For security reasons executing raw shell commands is only enabled by default for debug mode.
Here you can add any shell command to be executed inside a Queue job.
```
sh /some/shell.sh
```

This type does not need the param textarea as all args are directly passed along the command here.

The content is split on whitespace before dispatch: the first token becomes the
executable (matched against `Queue.executeAllowedCommands` verbatim) and each
remaining token is forwarded as its own argument (each `escapeshellarg`'d
individually by `Queue.Execute`). With debug off, the production allow-list
therefore lists executables: e.g. `bin/cake`, `/usr/bin/php`, `sh`: not full
command lines:

```php
'Queue' => [
    'executeAllowedCommands' => [
        'bin/cake',
        'sh',
    ],
],
```

Quote-aware tokenization is intentionally not performed; if you need a
composite shell line (pipes, redirection, embedded quoting) put it in a
wrapper script and schedule the script path instead.

## Job Config (queue routing & priority)

The optional **Job Config** field accepts a JSON object that is merged into
the `QueuedJobsTable::createJob()` call. Allowed keys:

| Key | Type | Effect |
|---|---|---|
| `priority` | int 1-10 | Lower runs sooner. Default is 5. |
| `group` | string | Worker group; matches `bin/cake queue run --group=...`. Lets you route scheduled jobs to a dedicated worker pool. |

Example: route a nightly cleanup to a low-priority batch worker:
```json
{"priority": 8, "group": "batch"}
```

Other `Queue\Config\JobConfig` keys (`notBefore`, `status`, `reference`) are
intentionally **not** accepted; `notBefore` is meaningless for cron-driven
dispatch (cron already controls timing), `reference` is set automatically
to `queue-scheduler-{id}`, and `status` is a runtime field overwritten on
the first progress tick. Unknown keys are rejected at save time so typos
like `prioirty` surface immediately instead of silently being ignored.

