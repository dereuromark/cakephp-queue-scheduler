# Frequencies and time windows

You can use different styles depending on your use case.

## Shortcuts
- `@yearly`
- `@monthly`
- `@weekly`
- `@daily`
- `@hourly`
- `@minutely`

Cron shortcuts use calendar boundaries. Interval frequencies start immediately when `last_run` is null, then calculate the next run from `last_run`. They do not use the row's `created` timestamp.

## Crontab style
For larger time frames (e.g. months) or more complex scheduling (e.g. "every Tuesday at ...") this style is recommended.
See [cron-expression](https://github.com/dragonmantank/cron-expression) for supported expressions. Enter the five scheduling fields only; the executable is stored separately in Content.

```text
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
```text
5 4 * * *
```

## DateInterval style

They either start with a `P` or a `+`. Other values are invalid.

- `P1D` and `+ 1 day` mean the same thing.
- `P2W` and `+ 2 weeks` mean the same thing.

You can also define more complex intervals by chaining: `+ 1 hour + 5 minutes`.

See PHP's [DateInterval string parser](https://www.php.net/manual/en/dateinterval.createfromdatestring.php) for relative intervals.

## Time-window restrictions (optional)

Cron expressions handle "every Monday at 9" but not "every 5 minutes, but
only between 09:00 to 18:00 on weekdays": those need compound restrictions
ANDed against the cron/interval firing. Three optional columns on
`queue_scheduler_rows` cover that:

| Column | Type | Meaning |
|---|---|---|
| `window_start_time` | time | Earliest time-of-day (server timezone) this row may dispatch. `null` = no lower bound. |
| `window_end_time` | time | Latest time-of-day. `null` = no upper bound. When end < start the interval wraps midnight (`22:00`–`06:00` means "overnight"). |
| `window_days_of_week` | string(32) | Comma-separated `0`–`6` (`0`=Sunday, `6`=Saturday). `null` = every day. |

All three are nullable and ANDed together. A row that doesn't set any of
them has no extra gating.

Examples:

```text
# "Every 5 min during business hours": cron + window combined:
frequency:            */5 * * * *
window_start_time:    09:00
window_end_time:      18:00
window_days_of_week:  1,2,3,4,5

# "Heavy report runs overnight only": end < start wraps midnight:
frequency:            */15 * * * *
window_start_time:    22:00
window_end_time:      06:00

# "Weekend backups only":
frequency:            @hourly
window_days_of_week:  0,6
```

When the window rejects, `isDue()` returns `false` even when the
cron/interval would otherwise fire. The next scheduler tick after the
window re-opens re-evaluates and dispatches normally.


