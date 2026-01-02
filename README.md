# Michel Lock

A flexible PHP locking library designed to prevent race conditions in CLI and web environments. It provides a simple `Locker` class that delegates the actual locking mechanism to a `LockHandlerInterface` implementation.

## Features

*   **Simple API**: Easy to use `lock()` and `unlock()` methods.
*   **Handler Abstraction**: Easily swap between different locking backends (e.g., `FlockHandler` for file-based locking).
*   **Automatic Cleanup**: Locks are automatically released when the `Locker` instance is destroyed or (optionally) on script shutdown.
*   **Blocking & Non-Blocking**: Supports both blocking (wait for lock) and non-blocking (fail immediately) modes.

## Installation

```bash
composer require michel/lock
```

## Usage

### Basic Usage (Non-Blocking)

By default or when passing `false` as the second argument, the lock attempt is non-blocking. It returns `false` immediately if the lock is already held by another process.

```php
<?php

use Michel\Lock\Locker;
use Michel\Lock\Handler\FlockHandler;

// 1. Create a handler (e.g., file-based locking)
$handler = new FlockHandler('/tmp/locks');

// 2. Instantiate the Locker
$locker = new Locker($handler);

// 3. Acquire a lock
// 'my_process' is the key.
// false = non-blocking mode (return false immediately if locked)
if ($locker->lock('my_process', false)) {
    echo "Lock acquired!\n";

    // Do some critical work...
    sleep(5);

    // 4. Release the lock
    $locker->unlock('my_process');
} else {
    echo "Could not acquire lock. Another process is running.\n";
}
```

### Blocking Mode (Wait for Lock)

If you want the script to pause and wait until the lock becomes available (e.g., waiting for another process to finish), set the second argument to `true`.

```php
// Try to acquire lock, waiting indefinitely until it is available
// true = blocking mode
if ($locker->lock('heavy_task', true)) {
    echo "Lock acquired after waiting (if necessary).\n";
    
    // Perform task
    // ...
    
    $locker->unlock('heavy_task');
}
```

### Automatic Unlock on Shutdown

You can ensure locks are released even if the script is killed or ends unexpectedly by using `unlockIfKill()`.

```php
$locker = new Locker($handler);
$locker->unlockIfKill(); // Registers a shutdown function

if ($locker->lock('critical_job')) {
    // ... work ...
}
// Lock will be released automatically when script ends
```

### Key Validation

Keys must be non-empty and contain only alphanumeric characters, underscores, and dots (`/^[a-zA-Z0-9_.]+$/`).

## License

MPL-2.0 License.
