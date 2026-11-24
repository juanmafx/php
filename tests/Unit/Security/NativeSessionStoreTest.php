<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Security;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use PhpLabs\Security\NativeSessionStore;

final class NativeSessionStoreTest extends TestCase
{
    private int $now = 100_000;

    private function store(): NativeSessionStore
    {
        $directory = sys_get_temp_dir() . '/php-labs-session-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        ini_set('session.save_path', $directory);
        ini_set('session.use_cookies', '0');

        return new NativeSessionStore(false, fn (): int => $this->now);
    }

    #[RunInSeparateProcess]
    public function testActiveSessionSurvives(): void
    {
        $store = $this->store();
        $store->set('key', 'value');
        session_write_close();
        $this->now += 1799;

        self::assertSame('value', $store->get('key'));
    }

    #[RunInSeparateProcess]
    public function testIdleSessionIsCleared(): void
    {
        $store = $this->store();
        $store->set('key', 'value');
        session_write_close();
        $this->now += 1800;

        self::assertNull($store->get('key'));
    }

    #[RunInSeparateProcess]
    public function testAbsoluteLifetimeIsEnforcedDespiteActivity(): void
    {
        $store = $this->store();
        $store->set('key', 'value');
        for ($elapsed = 0; $elapsed < 28_800; $elapsed += 1_500) {
            session_write_close();
            $this->now += 1_500;
            $value = $store->get('key');
        }

        self::assertNull($value ?? null);
    }

    #[RunInSeparateProcess]
    public function testRegenerationRestartsTheAbsoluteLifetime(): void
    {
        $store = $this->store();
        $store->set('key', 'value');
        session_write_close();
        $this->now += 1_500;
        $store->regenerate();
        session_write_close();
        $this->now += 1_500;

        self::assertSame('value', $store->get('key'));
    }
}
