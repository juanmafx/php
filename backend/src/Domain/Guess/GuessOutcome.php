<?php

declare(strict_types=1);

namespace PhpLabs\Domain\Guess;

enum GuessOutcome
{
    case Missing;
    case TooShort;
    case NotNumeric;
    case TooLow;
    case TooHigh;
    case Correct;

    public function message(): string
    {
        return match ($this) {
            self::Missing => 'Missing guess parameter',
            self::TooShort => 'Your guess is too short',
            self::NotNumeric => 'Your guess is not a number',
            self::TooLow => 'Your guess is too low',
            self::TooHigh => 'Your guess is too high',
            self::Correct => 'Congratulations - you are right',
        };
    }
}
