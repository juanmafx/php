<?php

declare(strict_types=1);

namespace PhpLabs\Domain\RockPaperScissors;

use Random\Randomizer;

final readonly class RandomMoveSource implements MoveSource
{
    public function __construct(private Randomizer $randomizer = new Randomizer())
    {
    }

    public function next(): Move
    {
        return Move::from($this->randomizer->getInt(0, 2));
    }
}
