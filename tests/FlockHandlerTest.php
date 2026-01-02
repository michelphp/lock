<?php

namespace Test\Michel\Lock;

use Michel\UniTester\TestCase;
use Michel\Lock\Handler\FlockHandler;

class FlockHandlerTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'flock_test_' . uniqid();
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->testDir);
        }
    }

    protected function execute(): void
    {
        $this->testLockFile();
        $this->testUnlockReleasesLock();
        $this->testLockNonBlockingFailsIfLocked();
        $this->testLockBlockingWaits();
    }

    private function testLockFile()
    {
        $handler = new FlockHandler($this->testDir);
        $key = 'test_lock_file';

        $bool = $handler->lock($key);
        $this->assertTrue($bool, 'FlockHandler::lock should return true on success');
        $expectedFile = $this->testDir . DIRECTORY_SEPARATOR . 'michel_lock_' . $key . '.lock';
        $this->assertFileExists($expectedFile, 'FlockHandler::lock should create a lock file');

        $ready = $handler->lock($key);
        $this->assertFalse($ready, 'FlockHandler::lock should return false if already locked');

        $handler->unlock($key);

        $ready = $handler->lock($key);
        $this->assertTrue($ready, 'FlockHandler::lock should return true after unlocking');

    }



    private function testUnlockReleasesLock()
    {
        $handler = new FlockHandler($this->testDir);
        $key = 'test_unlock';

        $handler->lock($key);
        $handler->unlock($key);
        $expectedFile = $this->testDir . DIRECTORY_SEPARATOR . 'michel_lock_' . $key . '.lock';
        $this->assertFileExists($expectedFile, 'FlockHandler::unlock should not delete the file');

    }

    private function testLockNonBlockingFailsIfLocked()
    {
        $key = 'concurrent_lock';
        $lockFile = $this->testDir . DIRECTORY_SEPARATOR . 'michel_lock_' . $key . '.lock';
        $tempScript = sys_get_temp_dir() . '/holder.php';
        file_put_contents($tempScript, '<?php $f=fopen($argv[1],"c"); flock($f,LOCK_EX); sleep(2);');
        $cmd = sprintf('php %s %s', escapeshellarg($tempScript), escapeshellarg($lockFile));
        $process = proc_open($cmd, [], $pipes);

        usleep(200000);

        $handler = new FlockHandler($this->testDir);
        $start = microtime(true);
        $result = $handler->lock($key, false); // Non-blocking
        $end = microtime(true);

        $this->assertFalse($result, 'Lock should fail if already locked by another process');
        $this->assertTrue(($end - $start) < 1, 'Non-blocking lock should return immediately');

        proc_terminate($process);
        proc_close($process);

        unlink($tempScript);

    }

    private function testLockBlockingWaits()
    {
        $key = 'blocking_lock';
        $lockFile = $this->testDir . DIRECTORY_SEPARATOR . 'michel_lock_' . $key . '.lock';
        $tempScript = sys_get_temp_dir() . '/holder.php';
        file_put_contents($tempScript, '<?php $f=fopen($argv[1],"c"); flock($f,LOCK_EX); sleep(2);');
        $cmd = sprintf('php %s %s', escapeshellarg($tempScript), escapeshellarg($lockFile));
        $process = proc_open($cmd, [], $pipes);

        usleep(200000);

        $handler = new FlockHandler($this->testDir);
        $start = microtime(true);
        $result = $handler->lock($key, true); // Blocking
        $end = microtime(true);

        $elapsed = $end - $start;
        $this->assertTrue($result, 'Blocking lock should eventually succeed');
        $this->assertTrue($elapsed >= 1.5, 'Blocking lock should wait for lock release (approx 1.5s remaining)');

        proc_terminate($process);
        proc_close($process);
        unlink($tempScript);

    }
}
