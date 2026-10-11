# Security

The admin can schedule application code. Limit access to trusted administrators and keep the backend behind your application's normal authentication.

## Access gate

`QueueScheduler.adminAccess` must be a `Closure` receiving the current request and returning literal `true`. An unset gate, a non-closure value, a non-boolean result, or an exception denies access with 403. The [beginner guide](./getting-started#allow-access-to-the-admin) includes an identity-based example.

When an Authorization component is loaded, the plugin calls `skipAuthorization()` because this gate makes the access decision. Application authentication and HTTP middleware still apply.

## Standalone mode

`QueueScheduler.standalone` defaults to `false`: admin controllers inherit your AppController setup and callbacks. A Response returned from the parent's `beforeFilter()` is preserved, including login redirects.

With `true`, the plugin bypasses AppController's `initialize()` and `beforeFilter()` and loads its own Flash component. The `adminAccess` gate still runs. Standalone mode does not disable HTTP middleware or every inherited callback; configure authentication middleware for these routes deliberately.

The admin layout is configured separately. Changing the layout does not change access permissions.

## Raw shell commands

Shell-command rows are active only with debug enabled or `QueueScheduler.allowRaw = true`. In production, Queue also checks the executable against `Queue.executeAllowedCommands`. Set both deliberately and allow only the executables you need. See [Task types](./tasks#scheduling-shell-commands) for tokenization and wrapper scripts.
