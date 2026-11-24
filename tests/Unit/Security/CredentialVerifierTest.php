<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use PhpLabs\Security\CredentialVerifier;

final class CredentialVerifierTest extends TestCase
{
    public function testVerifiesModernHash(): void
    {
        $verifier = new CredentialVerifier(password_hash('correct horse battery', PASSWORD_BCRYPT));

        self::assertTrue($verifier->isConfigured());
        self::assertTrue($verifier->verify('correct horse battery'));
        self::assertFalse($verifier->verify('wrong password'));
    }

    public function testFailsClosedWithoutHash(): void
    {
        self::assertFalse((new CredentialVerifier(null))->isConfigured());
        self::assertFalse((new CredentialVerifier(''))->verify(''));
    }

    public function testRejectsLegacyMd5Hash(): void
    {
        $legacyDigest = md5('sample-value');
        $verifier = new CredentialVerifier($legacyDigest);

        self::assertFalse($verifier->isConfigured());
        self::assertFalse($verifier->verify('sample-value'));
    }

    public function testRejectsOversizedPassword(): void
    {
        $password = str_repeat('a', 4097);

        self::assertFalse((new CredentialVerifier(password_hash($password, PASSWORD_ARGON2ID)))->verify($password));
    }

    public function testRejectsPasswordWithNulByte(): void
    {
        $verifier = new CredentialVerifier(password_hash('abcdefghijkl', PASSWORD_BCRYPT));

        self::assertFalse($verifier->verify("abcdefghijkl\0suffix"));
    }

    public function testRejectsBcryptInputBeyondItsLimit(): void
    {
        $password = str_repeat('a', 72);
        $verifier = new CredentialVerifier(password_hash($password, PASSWORD_BCRYPT));

        self::assertTrue($verifier->verify($password));
        self::assertFalse($verifier->verify($password . 'different-suffix'));
    }
}
