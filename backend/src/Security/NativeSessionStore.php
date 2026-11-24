<?php

declare(strict_types=1);

namespace PhpLabs\Security;

use Closure;
use RuntimeException;

final readonly class NativeSessionStore implements SessionStore
{
    private const string COOKIE_NAME = 'php_labs_session';
    private const string STARTED_AT_KEY = '_started_at';
    private const string LAST_SEEN_AT_KEY = '_last_seen_at';
    private const int IDLE_TIMEOUT_SECONDS = 1800;
    private const int ABSOLUTE_TIMEOUT_SECONDS = 28800;

    private Closure $clock;

    public function __construct(private bool $secureCookie, ?Closure $clock = null)
    {
        $this->clock = $clock ?? time(...);
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
        $_SESSION[self::STARTED_AT_KEY] = $this->now();
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

        $this->enforceLifetime();
    }

    private function enforceLifetime(): void
    {
        $now = $this->now();
        $startedAt = $_SESSION[self::STARTED_AT_KEY] ?? null;
        $lastSeenAt = $_SESSION[self::LAST_SEEN_AT_KEY] ?? null;
        $isExpired = is_int($startedAt) && is_int($lastSeenAt)
            && ($now - $lastSeenAt >= self::IDLE_TIMEOUT_SECONDS || $now - $startedAt >= self::ABSOLUTE_TIMEOUT_SECONDS);

        if ($isExpired) {
            $_SESSION = [];
            session_regenerate_id(true);
        }

        $_SESSION[self::STARTED_AT_KEY] = !$isExpired && is_int($startedAt) ? $startedAt : $now;
        $_SESSION[self::LAST_SEEN_AT_KEY] = $now;
    }

    private function now(): int
    {
        $now = ($this->clock)();

        return is_int($now) ? $now : time();
    }
}
