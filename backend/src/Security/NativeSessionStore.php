<?php

declare(strict_types=1);

namespace PhpLabs\Security;

use RuntimeException;

final readonly class NativeSessionStore implements SessionStore
{
    private const string COOKIE_NAME = 'php_labs_session';

    public function __construct(private bool $secureCookie)
    {
    }

    public function get(string $key): ?string
    {
        $this->start();
        $value = $_SESSION[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function set(string $key, string $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        session_destroy();
        setcookie(self::COOKIE_NAME, '', [
            'expires' => 1,
            'path' => '/',
            'secure' => $this->secureCookie,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $started = session_start([
            'name' => self::COOKIE_NAME,
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_path' => '/',
            'cookie_httponly' => true,
            'cookie_secure' => $this->secureCookie,
            'cookie_samesite' => 'Strict',
            'cookie_lifetime' => 0,
            'gc_maxlifetime' => 1800,
        ]);

        if (!$started) {
            throw new RuntimeException('Unable to start the session.');
        }
    }
}
