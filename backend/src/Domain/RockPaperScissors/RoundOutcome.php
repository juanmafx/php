<?php

declare(strict_types=1);

namespace PhpLabs\Domain\RockPaperScissors;

enum RoundOutcome: string
{
    case Tie = 'Tie';
    case Win = 'You Win';
    case Lose = 'You Lose';
}
