<?php

declare(strict_types=1);

namespace PhpLabs\Domain\RockPaperScissors;

interface MoveSource
{
    public function next(): Move;
}
