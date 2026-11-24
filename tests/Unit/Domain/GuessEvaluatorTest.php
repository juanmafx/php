<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpLabs\Domain\Guess\GuessEvaluator;
use PhpLabs\Domain\Guess\GuessOutcome;

final class GuessEvaluatorTest extends TestCase
{
    public static function guesses(): array
    {
        return [
            'missing' => [null, GuessOutcome::Missing],
            'empty' => ['', GuessOutcome::TooShort],
            'text' => ['forty', GuessOutcome::NotNumeric],
            'markup' => ['<b>42</b>', GuessOutcome::NotNumeric],
            'oversized' => [str_repeat('1', 33), GuessOutcome::NotNumeric],
            'low' => ['41', GuessOutcome::TooLow],
            'negative' => ['-42', GuessOutcome::TooLow],
            'high' => ['43', GuessOutcome::TooHigh],
            'overflow' => ['1e999', GuessOutcome::TooHigh],
            'exact' => ['42', GuessOutcome::Correct],
            'decimal form' => ['42.0', GuessOutcome::Correct],
        ];
    }

    #[DataProvider('guesses')]
    public function testEvaluatesGuess(?string $guess, GuessOutcome $expected): void
    {
        self::assertSame($expected, (new GuessEvaluator())->evaluate($guess));
    }

    public function testEveryOutcomeHasAMessage(): void
    {
        foreach (GuessOutcome::cases() as $outcome) {
            self::assertNotSame('', $outcome->message());
        }
    }
}
