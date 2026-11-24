<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use PhpLabs\Security\CsrfGuard;
use PhpLabs\Tests\Support\InMemorySessionStore;

final class CsrfGuardTest extends TestCase
{
    public function testTokenIsStableAndValid(): void
    {
        $guard = new CsrfGuard(new InMemorySessionStore());
        $token = $guard->token();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $guard->token());
        self::assertTrue($guard->isValid($token));
    }

    public function testRejectsMissingOrWrongToken(): void
    {
        $guard = new CsrfGuard(new InMemorySessionStore());

        self::assertFalse($guard->isValid(null));
        $guard->token();
        self::assertFalse($guard->isValid(null));
        self::assertFalse($guard->isValid(str_repeat('0', 64)));
    }

    public function testRotationInvalidatesPreviousToken(): void
    {
        $guard = new CsrfGuard(new InMemorySessionStore());
        $previous = $guard->token();
        $guard->rotate();

        self::assertFalse($guard->isValid($previous));
        self::assertNotSame($previous, $guard->token());
    }
}
