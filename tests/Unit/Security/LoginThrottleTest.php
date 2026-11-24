<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use PhpLabs\Security\LoginThrottle;
use RuntimeException;

final class LoginThrottleTest extends TestCase
{
    private string $directory;
    private int $now = 1_000;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/php-labs-test-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->directory . '/*') ?: []);
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    private function throttle(): LoginThrottle
    {
        return new LoginThrottle($this->directory, fn (): int => $this->now);
    }

    public function testBlocksAfterFiveFailures(): void
    {
        $throttle = $this->throttle();
        for ($failure = 0; $failure < 4; $failure++) {
            $throttle->tryAcquire('client');
        }
        self::assertFalse($throttle->isBlocked('client'));

        $throttle->tryAcquire('client');
        self::assertTrue($throttle->isBlocked('client'));
        self::assertFalse($throttle->isBlocked('other-client'));
    }

    public function testWindowExpires(): void
    {
        $throttle = $this->throttle();
        for ($failure = 0; $failure < 5; $failure++) {
            $throttle->tryAcquire('client');
        }

        $this->now += 900;

        self::assertFalse($throttle->isBlocked('client'));
    }

    public function testResetClearsFailures(): void
    {
        $throttle = $this->throttle();
        for ($failure = 0; $failure < 5; $failure++) {
            $throttle->tryAcquire('client');
        }
        $throttle->reset('client');

        self::assertFalse($throttle->isBlocked('client'));
    }

    public function testStateFileIsPrivateAndDoesNotContainClientKey(): void
    {
        $this->throttle()->tryAcquire('203.0.113.9');
        $files = glob($this->directory . '/*') ?: [];

        self::assertCount(1, $files);
        self::assertSame(0600, fileperms($files[0]) & 0777);
        self::assertStringNotContainsString('203.0.113.9', $files[0] . file_get_contents($files[0]));
    }

    public function testAcquireStopsAtTheLimit(): void
    {
        $throttle = $this->throttle();
        $granted = 0;
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $granted += $throttle->tryAcquire('client') ? 1 : 0;
        }

        self::assertSame(5, $granted);
        self::assertTrue($throttle->isBlocked('client'));
    }

    public function testConcurrentProcessesCannotExceedTheLimit(): void
    {
        $this->throttle()->reset('client');
        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        $code = sprintf(
            'require %s; $t = new PhpLabs\Security\LoginThrottle(%s); $n = 0; for ($i = 0; $i < 5; $i++) { $n += $t->tryAcquire("client") ? 1 : 0; } echo $n;',
            var_export($autoload, true),
            var_export($this->directory, true),
        );
        $processes = [];
        for ($worker = 0; $worker < 8; $worker++) {
            $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $processes[] = [$process, $pipes[1]];
        }

        $granted = 0;
        foreach ($processes as [$process, $output]) {
            $granted += (int) stream_get_contents($output);
            fclose($output);
            proc_close($process);
        }

        self::assertSame(5, $granted);
    }

    public function testIpv6ClientsShareASlash64Bucket(): void
    {
        $throttle = $this->throttle();
        for ($host = 1; $host <= 5; $host++) {
            self::assertTrue($throttle->tryAcquire('2001:db8:1:2::' . dechex($host)));
        }

        self::assertTrue($throttle->isBlocked('2001:db8:1:2:ffff::1'));
        self::assertFalse($throttle->isBlocked('2001:db8:1:3::1'));
    }

    public function testIpv4MappedAddressSharesTheIpv4Bucket(): void
    {
        $throttle = $this->throttle();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $throttle->tryAcquire('203.0.113.9');
        }

        self::assertTrue($throttle->isBlocked('::ffff:203.0.113.9'));
    }

    public function testRefusesDirectoryWritableByOthers(): void
    {
        mkdir($this->directory, 0700);
        chmod($this->directory, 0777);

        $this->expectException(RuntimeException::class);
        try {
            $this->throttle();
        } finally {
            chmod($this->directory, 0700);
        }
    }

    public function testRefusesSymlinkedDirectory(): void
    {
        mkdir($this->directory, 0700);
        $link = $this->directory . '-link';
        symlink($this->directory, $link);

        try {
            new LoginThrottle($link);
            self::fail('A symlinked throttle directory was accepted.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('private', $exception->getMessage());
        } finally {
            unlink($link);
        }
    }
}
