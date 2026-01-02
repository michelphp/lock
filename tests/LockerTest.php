<?php

namespace Test\Michel\Lock;

use Michel\UniTester\TestCase;

class LockerTest extends TestCase
{
    protected function setUp(): void {}

    protected function tearDown(): void {}

    protected function execute(): void
    {
        $this->testLockDelegatesToHandler();
        $this->testLockThrowsExceptionForInvalidKey();
        $this->testUnlockDelegatesToHandler();
        $this->testUnlockThrowsExceptionIfKeyNotLocked();
        $this->testUnlockIfKillReturnsSelf();
        $this->testDestructReleasesLocks();
    }
    public function testLockDelegatesToHandler()
    {
        $handler = new class implements \Michel\Lock\LockHandlerInterface {
            private bool $locked = false;
            public function lock(string $key, bool $wait = false): bool
            {
                if ($this->locked) {
                    return false;
                }
                $this->locked = true;
                return true;
            }
            public function unlock(string $key): void {
                $this->locked = false;
            }

            public function isLocked(): bool
            {
                return $this->locked;
            }
        };

        $locker = new \Michel\Lock\Locker($handler);
        $result = $locker->lock('test_key');
        $this->assertTrue($result, 'Locker::lock should return true when handler succeeds');
        $this->assertTrue($handler->isLocked(), 'Locker::lock should call handler->lock');

        $ready = $locker->lock('test_key');
        $this->assertFalse($ready, 'Locker::lock should return false if already locked');
        $this->assertTrue($handler->isLocked() === true, 'Locker::lock should call handler->lock');

        $locker->unlock('test_key');
        $this->assertTrue($handler->isLocked() === false, 'Locker::unlock should call handler->unlock');
    }

    public function testLockThrowsExceptionForInvalidKey()
    {
        $handler = new class implements \Michel\Lock\LockHandlerInterface {
            public function lock(string $key, bool $wait = false): bool
            {
                return true;
            }
            public function unlock(string $key): void {}
        };

        $locker = new \Michel\Lock\Locker($handler);

        $this->expectException(\InvalidArgumentException::class, function () use ($locker) {
            $locker->lock('invalid key!');
        });

        $this->expectException(\InvalidArgumentException::class, function () use ($locker) {
            $locker->lock('');
        });
    }

    public function testUnlockDelegatesToHandler()
    {
        $handler = new class implements \Michel\Lock\LockHandlerInterface {
            public bool $unlocked = false;
            public function lock(string $key, bool $wait = false): bool
            {
                return true;
            }
            public function unlock(string $key): void
            {
                $this->unlocked = true;
            }
        };

        $locker = new \Michel\Lock\Locker($handler);
        $locker->lock('test_key');
        $locker->unlock('test_key');

        $this->assertTrue($handler->unlocked, 'Locker::unlock should call handler->unlock');
    }

    public function testUnlockThrowsExceptionIfKeyNotLocked()
    {
        $handler = new class implements \Michel\Lock\LockHandlerInterface {
            public function lock(string $key, bool $wait = false): bool
            {
                return true;
            }
            public function unlock(string $key): void {}
        };

        $locker = new \Michel\Lock\Locker($handler);

        $this->expectException(\InvalidArgumentException::class, function () use ($locker) {
            $locker->unlock('unknown_key');
        });
    }

    public function testUnlockIfKillReturnsSelf()
    {
        $handler = new class implements \Michel\Lock\LockHandlerInterface {
            public function lock(string $key, bool $wait = false): bool { return true; }
            public function unlock(string $key): void {}
        };
        $locker = new \Michel\Lock\Locker($handler);
        $this->assertTrue($locker->unlockIfKill() === $locker, 'unlockIfKill should return self');
    }

    public function testDestructReleasesLocks()
    {
        $handler = new class implements \Michel\Lock\LockHandlerInterface {
            public int $unlockCalls = 0;
            public function lock(string $key, bool $wait = false): bool { return true; }
            public function unlock(string $key): void { $this->unlockCalls++; }
        };

        $locker = new \Michel\Lock\Locker($handler);
        $locker->lock('key1');
        $locker->lock('key2');

        unset($locker); // Trigger destruct

        $this->assertTrue($handler->unlockCalls === 2, 'Destructor should unlock all held locks');
    }

}
