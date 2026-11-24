<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Support;

use PhpLabs\Domain\RockPaperScissors\Move;
use PhpLabs\Domain\RockPaperScissors\MoveSource;

final readonly class FixedMoveSource implements MoveSource
{
    public function __construct(private Move $move)
    {
    }

    public function next(): Move
    {
        return $this->move;
    }
}
