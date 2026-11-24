<?php

declare(strict_types=1);

namespace PhpLabs\Domain\RockPaperScissors;

enum Move: int
{
    case Rock = 0;
    case Paper = 1;
    case Scissors = 2;

    public static function fromInput(?string $input): ?self
    {
        return $input !== null && preg_match('/\A[0-2]\z/', $input) === 1
            ? self::from((int) $input)
            : null;
    }

    public function beats(self $other): bool
    {
        return (($this->value - $other->value + 3) % 3) === 1;
    }
}
