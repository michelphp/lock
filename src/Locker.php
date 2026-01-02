<?php

namespace Michel\Lock;

use Exception;
use InvalidArgumentException;

final class Locker
{
    private LockHandlerInterface $lockHandler;
    private array $handles = [];

    public function __construct(LockHandlerInterface $lockHandler)
    {
        $this->lockHandler = $lockHandler;
    }

    public function unlockIfKill(): self
    {
        register_shutdown_function([$this, 'unlockAll']);
        return $this;
    }

    public function lock(string $key, bool $wait = false): bool
    {
        if (empty($key)) {
            throw new InvalidArgumentException(
                'Locker::lock() method error: A non-empty key (ex: "my_key", "my.key") must be defined.'
            );
        }

        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $key)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid key name "%s": only alphanumeric characters, underscores (_), and dots (.) are allowed.',
                $key
            ));
        }
        $value = $this->lockHandler->lock($key, $wait);
        if ($value === true) {
            $this->handles[$key] = true;
        }
        return $value;
    }

    public function unlock(string $key): void
    {
        if (empty($key)) {
            throw new \InvalidArgumentException(
                'Locker::unlock() failed: The key cannot be empty. Please provide the string identifier used during the lock() call.'
            );
        }

        if (!isset($this->handles[$key])) {
            throw new \InvalidArgumentException(sprintf(
                'Locker::unlock() failed: The key "%s" is not currently managed by this Locker instance. ' .
                'Ensure the key is correct and that lock() returned true before unlocking.',
                $key
            ));
        }
        $this->lockHandler->unlock($key);
        unset($this->handles[$key]);
    }

    public function __destruct()
    {
        $this->unlockAll();
    }

    private function unlockAll(): void
    {
        foreach (array_keys($this->handles) as $key) {
            try {
                $this->unlock($key);
            } catch (Exception $e) {
                error_log(sprintf("%s::%s : %s", __CLASS__, __FUNCTION__, $e->getMessage()));
            }
        }
    }
}
