<?php

declare(strict_types=1);

namespace PhpLabs\Security;

use Closure;
use RuntimeException;

final readonly class LoginThrottle
{
    private const int MAX_FAILURES = 5;
    private const int WINDOW_SECONDS = 900;

    private Closure $clock;

    public function __construct(private string $directory, ?Closure $clock = null)
    {
        $this->clock = $clock ?? time(...);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the throttle directory.');
        }
        $this->assertPrivateDirectory();
    }

    public function isBlocked(string $clientKey): bool
    {
        return $this->update($clientKey, 0, false) >= self::MAX_FAILURES;
    }

    public function tryAcquire(string $clientKey): bool
    {
        return $this->update($clientKey, 1, false) < self::MAX_FAILURES;
    }

    public function reset(string $clientKey): void
    {
        $this->update($clientKey, 0, true);
    }

    private function update(string $clientKey, int $increment, bool $clear): int
    {
        $path = $this->directory . '/' . hash('sha256', self::bucket($clientKey)) . '.json';
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the throttle state.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the throttle state.');
            }
            chmod($path, 0600);

            $now = $this->now();
            $decoded = json_decode((string) stream_get_contents($handle), true);
            $storedFailures = is_array($decoded) ? ($decoded['failures'] ?? null) : null;
            $storedStart = is_array($decoded) ? ($decoded['started'] ?? null) : null;
            $isCurrent = is_int($storedStart) && $now - $storedStart < self::WINDOW_SECONDS;

            $previous = !$clear && $isCurrent && is_int($storedFailures) ? $storedFailures : 0;
            $failures = min($previous + $increment, self::MAX_FAILURES);
            $started = !$clear && $isCurrent ? $storedStart : $now;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode(['failures' => $failures, 'started' => $started], JSON_THROW_ON_ERROR));
            fflush($handle);

            return $previous;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function assertPrivateDirectory(): void
    {
        clearstatcache(true, $this->directory);
        $status = lstat($this->directory);
        $isOwned = $status !== false && (!function_exists('posix_geteuid') || $status['uid'] === posix_geteuid());

        if ($status === false || is_link($this->directory) || !is_dir($this->directory) || !$isOwned || ($status['mode'] & 0077) !== 0) {
            throw new RuntimeException('The throttle directory must be a private directory owned by the application user.');
        }
    }

    private static function bucket(string $clientKey): string
    {
        if (filter_var($clientKey, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return $clientKey;
        }

        $packed = (string) inet_pton($clientKey);
        if (str_starts_with($packed, str_repeat("\0", 10) . "\xff\xff")) {
            return (string) inet_ntop(substr($packed, 12));
        }

        return bin2hex(substr($packed, 0, 8)) . '/64';
    }

    private function now(): int
    {
        $now = ($this->clock)();

        return is_int($now) ? $now : time();
    }
}
