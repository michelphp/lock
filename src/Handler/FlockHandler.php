<?php

namespace Michel\Lock\Handler;

use Michel\Lock\LockHandlerInterface;

class FlockHandler implements LockHandlerInterface
{
    private string $lockDir;
    private array $resources = [];
    public function __construct(string $lockDir = null)
    {
        $this->lockDir = $lockDir ?? sys_get_temp_dir();
    }
    public function lock(string $key, bool $wait = false): bool
    {
        $filePath = $this->lockDir . DIRECTORY_SEPARATOR . 'michel_lock_' . $key.'.lock';
        $handle = fopen($filePath, 'c');

        if (!$handle) {
            return false;
        }

        $flags = $wait ? LOCK_EX : LOCK_EX | LOCK_NB;
        if (flock($handle, $flags)) {
            $this->resources[$key] = $handle;
            return true;
        }

        fclose($handle);
        return false;
    }

    public function unlock(string $key): void
    {
        if (isset($this->resources[$key])) {
            flock($this->resources[$key], LOCK_UN);
            fclose($this->resources[$key]);
            unset($this->resources[$key]);
        }
    }
}
