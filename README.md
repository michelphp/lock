# Michel Lock

**One process at a time.**

Two cron runs, two workers, two requests: only one does the job. Lock a key, do the work, unlock. Plain PHP file locks by default, your own backend when you need it.

PHP 7.4+ · No dependency · CLI and web

📖 **[Documentation](https://michelphp.github.io/lock/docs.html)** · [Website](https://michelphp.github.io/lock/)

## Install

```bash
composer require michel/lock
```

## Quick start

```php
use Michel\Lock\Locker;
use Michel\Lock\Handler\FlockHandler;

$locker = new Locker(new FlockHandler());

if (!$locker->lock('nightly_import')) {
    echo "Already running.\n";
    exit(0);
}

runImport(); // only one process at a time runs this

$locker->unlock('nightly_import');
```

Wait for the lock instead of giving up:

```php
$locker->lock('counter', true); // blocks until the lock is free
```

## Why Lock

| | |
|---|---|
| **No dependency** | Pure PHP 7.4+. No server, no extension: the default handler uses `flock()`. |
| **Never stuck** | Locks are released by `unlock()`, when the `Locker` is destroyed, and at shutdown with `unlockIfKill()`. If the process is killed, the system releases the file lock. |
| **Any backend** | Implement `LockHandlerInterface` (two methods) to share a lock between servers, on your database or Redis. |
| **Safe keys** | Keys accept letters, digits, `_` and `.` only: a key can never become a path. |

## What Lock will never do

add a dependency · require a server · retry on a timer · expire a lock behind your back · accept a key that is a path

## Documentation

The [documentation](https://michelphp.github.io/lock/docs.html) covers keys, blocking and non-blocking locks, releasing, `FlockHandler`, writing your own handler and errors.

## License

[MPL-2.0](LICENSE)
