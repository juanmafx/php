<?php

declare(strict_types=1);

namespace PhpLabs\Domain\RockPaperScissors;

final class RoundJudge
{
    public function judge(Move $human, Move $computer): RoundOutcome
    {
        return match (true) {
            $human === $computer => RoundOutcome::Tie,
            $human->beats($computer) => RoundOutcome::Win,
            default => RoundOutcome::Lose,
        };
    }

    public function matrix(): array
    {
        $rows = [];
        foreach (Move::cases() as $computer) {
            foreach (Move::cases() as $human) {
                $rows[] = ['human' => $human, 'computer' => $computer, 'outcome' => $this->judge($human, $computer)];
            }
        }

        return $rows;
    }
}
