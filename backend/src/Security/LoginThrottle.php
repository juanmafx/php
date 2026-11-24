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
    }

    public function isBlocked(string $clientKey): bool
    {
        return $this->update($clientKey, 0, false) >= self::MAX_FAILURES;
    }

    public function recordFailure(string $clientKey): void
    {
        $this->update($clientKey, 1, false);
    }

    public function reset(string $clientKey): void
    {
        $this->update($clientKey, 0, true);
    }

    private function update(string $clientKey, int $increment, bool $clear): int
    {
        $path = $this->directory . '/' . hash('sha256', $clientKey) . '.json';
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

            $failures = !$clear && $isCurrent && is_int($storedFailures) ? $storedFailures + $increment : $increment;
            $started = !$clear && $isCurrent ? $storedStart : $now;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode(['failures' => $failures, 'started' => $started], JSON_THROW_ON_ERROR));
            fflush($handle);

            return $failures;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function now(): int
    {
        $now = ($this->clock)();

        return is_int($now) ? $now : time();
    }
}
