<?php

declare(strict_types=1);

namespace PhpLabs\Http\Controller;

use PhpLabs\Domain\Guess\GuessEvaluator;
use PhpLabs\Domain\Guess\GuessOutcome;
use PhpLabs\Http\Request;
use PhpLabs\Http\Response;
use PhpLabs\View\TemplateRenderer;

final readonly class GuessController
{
    public function __construct(private TemplateRenderer $view, private GuessEvaluator $evaluator)
    {
    }

    public function show(Request $request): Response
    {
        $guess = $request->query('guess');
        $outcome = $this->evaluator->evaluate($guess);

        return Response::html($this->view->page('guess', 'Guessing Game', [
            'guess' => $guess,
            'message' => $outcome->message(),
            'isCorrect' => $outcome === GuessOutcome::Correct,
        ]));
    }
}
