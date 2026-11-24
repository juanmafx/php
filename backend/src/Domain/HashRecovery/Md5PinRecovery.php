<?php

declare(strict_types=1);

namespace PhpLabs\Domain\HashRecovery;

use InvalidArgumentException;

final class Md5PinRecovery
{
    private const int KEYSPACE = 10_000;

    public static function isValidDigest(string $digest): bool
    {
        return preg_match('/\A[a-f0-9]{32}\z/i', $digest) === 1;
    }

    public function recover(string $digest): RecoveryResult
    {
        if (!self::isValidDigest($digest)) {
            throw new InvalidArgumentException('Expected a 32-character hexadecimal MD5 digest.');
        }

        $normalized = strtolower($digest);

        for ($candidate = 0; $candidate < self::KEYSPACE; $candidate++) {
            $pin = sprintf('%04d', $candidate);
            if (hash_equals($normalized, hash('md5', $pin))) {
                return new RecoveryResult($pin, $candidate + 1);
            }
        }

        return new RecoveryResult(null, self::KEYSPACE);
    }
}
