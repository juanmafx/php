<?php

declare(strict_types=1);

namespace PhpLabs\Domain\Guess;

final readonly class GuessEvaluator
{
    private const int MAX_INPUT_LENGTH = 32;

    public function __construct(private int $target = 42)
    {
    }

    public function evaluate(?string $guess): GuessOutcome
    {
        if ($guess === null) {
            return GuessOutcome::Missing;
        }
        if ($guess === '') {
            return GuessOutcome::TooShort;
        }
        if (strlen($guess) > self::MAX_INPUT_LENGTH || !is_numeric($guess)) {
            return GuessOutcome::NotNumeric;
        }

        $value = (float) $guess;

        return match (true) {
            $value < $this->target => GuessOutcome::TooLow,
            $value > $this->target => GuessOutcome::TooHigh,
            default => GuessOutcome::Correct,
        };
    }
}
