<?php

declare(strict_types=1);

namespace PhpLabs\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HashPasswordCommandTest extends TestCase
{
    private function runCommand(string $input): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__, 2) . '/backend/bin/hash-password'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $output = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), trim($output)];
    }

    public function testCreatesAVerifiableHash(): void
    {
        [$exitCode, $hash] = $this->runCommand("a-long-enough-passphrase\n");

        self::assertSame(0, $exitCode);
        self::assertIsString($hash);
        self::assertTrue(password_verify('a-long-enough-passphrase', $hash));
    }

    public static function rejectedPasswords(): array
    {
        return [
            'too short' => ['short'],
            'nul byte' => ["a-long-enough\0passphrase"],
            'oversized' => [str_repeat('a', 4097)],
        ];
    }

    #[DataProvider('rejectedPasswords')]
    public function testRejectsUnusablePasswords(string $password): void
    {
        [$exitCode, $output] = $this->runCommand($password);

        self::assertSame(1, $exitCode);
        self::assertSame('', $output);
    }
}
