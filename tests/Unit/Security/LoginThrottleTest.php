<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use PhpLabs\Security\LoginThrottle;

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
        rmdir($this->directory);
    }

    private function throttle(): LoginThrottle
    {
        return new LoginThrottle($this->directory, fn (): int => $this->now);
    }

    public function testBlocksAfterFiveFailures(): void
    {
        $throttle = $this->throttle();
        for ($failure = 0; $failure < 4; $failure++) {
            $throttle->recordFailure('client');
        }
        self::assertFalse($throttle->isBlocked('client'));

        $throttle->recordFailure('client');
        self::assertTrue($throttle->isBlocked('client'));
        self::assertFalse($throttle->isBlocked('other-client'));
    }

    public function testWindowExpires(): void
    {
        $throttle = $this->throttle();
        for ($failure = 0; $failure < 5; $failure++) {
            $throttle->recordFailure('client');
        }

        $this->now += 900;

        self::assertFalse($throttle->isBlocked('client'));
    }

    public function testResetClearsFailures(): void
    {
        $throttle = $this->throttle();
        for ($failure = 0; $failure < 5; $failure++) {
            $throttle->recordFailure('client');
        }
        $throttle->reset('client');

        self::assertFalse($throttle->isBlocked('client'));
    }

    public function testStateFileIsPrivateAndDoesNotContainClientKey(): void
    {
        $this->throttle()->recordFailure('203.0.113.9');
        $files = glob($this->directory . '/*') ?: [];

        self::assertCount(1, $files);
        self::assertSame(0600, fileperms($files[0]) & 0777);
        self::assertStringNotContainsString('203.0.113.9', $files[0] . file_get_contents($files[0]));
    }
}
