<?php

namespace Michel\Lock;

interface LockHandlerInterface
{
    /**
     * Attempts to acquire an exclusive lock for the given key.
     *
     * @param string $key  The unique identifier for the lock.
     * @param bool $wait   Whether to wait (blocking mode) until the lock becomes
     * available or return false immediately.
     * * @return bool True if the lock was successfully acquired, false otherwise.
     */
    public function lock(string $key, bool $wait = false): bool;

    /**
     * Releases the lock associated with the given key.
     *
     * @param string $key The unique identifier for the lock to be released.
     */
    public function unlock(string $key): void;
}
