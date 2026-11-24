<?php

declare(strict_types=1);

namespace PhpLabs\Domain\HashRecovery;

final readonly class RecoveryResult
{
    public function __construct(public ?string $pin, public int $attempts)
    {
    }
}
