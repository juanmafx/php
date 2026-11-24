<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpLabs\Domain\RockPaperScissors\Move;
use PhpLabs\Domain\RockPaperScissors\RandomMoveSource;
use PhpLabs\Domain\RockPaperScissors\RoundJudge;
use PhpLabs\Domain\RockPaperScissors\RoundOutcome;

final class RoundJudgeTest extends TestCase
{
    public static function rounds(): array
    {
        return [
            [Move::Rock, Move::Rock, RoundOutcome::Tie],
            [Move::Rock, Move::Paper, RoundOutcome::Lose],
            [Move::Rock, Move::Scissors, RoundOutcome::Win],
            [Move::Paper, Move::Rock, RoundOutcome::Win],
            [Move::Paper, Move::Paper, RoundOutcome::Tie],
            [Move::Paper, Move::Scissors, RoundOutcome::Lose],
            [Move::Scissors, Move::Rock, RoundOutcome::Lose],
            [Move::Scissors, Move::Paper, RoundOutcome::Win],
            [Move::Scissors, Move::Scissors, RoundOutcome::Tie],
        ];
    }

    #[DataProvider('rounds')]
    public function testJudgesRound(Move $human, Move $computer, RoundOutcome $expected): void
    {
        self::assertSame($expected, (new RoundJudge())->judge($human, $computer));
    }

    public function testMatrixCoversEveryCombination(): void
    {
        self::assertCount(9, (new RoundJudge())->matrix());
    }

    public static function moveInputs(): array
    {
        return [['0', Move::Rock], ['1', Move::Paper], ['2', Move::Scissors], ['3', null], ['-1', null], ['1 ', null], ["1\n", null], ['', null], [null, null]];
    }

    #[DataProvider('moveInputs')]
    public function testParsesMoveInput(?string $input, ?Move $expected): void
    {
        self::assertSame($expected, Move::fromInput($input));
    }

    public function testRandomSourceEventuallyProducesEveryMove(): void
    {
        $source = new RandomMoveSource();
        $seen = [];
        for ($round = 0; $round < 500; $round++) {
            $seen[$source->next()->name] = true;
        }

        self::assertCount(3, $seen);
    }
}
