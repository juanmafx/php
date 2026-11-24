<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpLabs\Domain\HashRecovery\Md5PinRecovery;

final class Md5PinRecoveryTest extends TestCase
{
    public static function pins(): array
    {
        return [['0000', 1], ['0042', 43], ['1234', 1235], ['9999', 10000]];
    }

    #[DataProvider('pins')]
    public function testRecoversPinAndStopsAtFirstMatch(string $pin, int $attempts): void
    {
        $result = (new Md5PinRecovery())->recover(md5($pin));

        self::assertSame($pin, $result->pin);
        self::assertSame($attempts, $result->attempts);
    }

    public function testAcceptsUppercaseDigest(): void
    {
        self::assertSame('1234', (new Md5PinRecovery())->recover(strtoupper(md5('1234')))->pin);
    }

    public function testReportsExhaustedKeyspace(): void
    {
        $result = (new Md5PinRecovery())->recover(md5('not-a-pin'));

        self::assertNull($result->pin);
        self::assertSame(10000, $result->attempts);
    }

    public function testDoesNotTreatMagicHashAsNumericEquality(): void
    {
        self::assertNull((new Md5PinRecovery())->recover('0e000000000000000000000000000000')->pin);
    }

    public static function invalidDigests(): array
    {
        return [[''], ['abc'], [str_repeat('g', 32)], [md5('1') . "\n"], [str_repeat('a', 33)]];
    }

    #[DataProvider('invalidDigests')]
    public function testRejectsInvalidDigest(string $digest): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Md5PinRecovery())->recover($digest);
    }
}
