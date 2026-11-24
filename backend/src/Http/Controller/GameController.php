<?php

declare(strict_types=1);

namespace PhpLabs\Http\Controller;

use PhpLabs\Domain\RockPaperScissors\Move;
use PhpLabs\Domain\RockPaperScissors\MoveSource;
use PhpLabs\Domain\RockPaperScissors\RoundJudge;
use PhpLabs\Http\Request;
use PhpLabs\Http\Response;
use PhpLabs\Security\CsrfGuard;
use PhpLabs\Security\SessionStore;
use PhpLabs\View\TemplateRenderer;

final readonly class GameController
{
    public function __construct(
        private TemplateRenderer $view,
        private SessionStore $session,
        private CsrfGuard $csrf,
        private RoundJudge $judge,
        private MoveSource $moves,
    ) {
    }

    public function show(Request $request): Response
    {
        $player = $this->session->get(AuthController::PLAYER_KEY);

        return $player === null ? Response::redirect('/login') : $this->render($player, []);
    }

    public function play(Request $request): Response
    {
        $player = $this->session->get(AuthController::PLAYER_KEY);
        if ($player === null) {
            return Response::redirect('/login');
        }

        $input = $request->form('human');
        if ($input === 'matrix') {
            return $this->render($player, ['matrix' => $this->judge->matrix()]);
        }

        $human = Move::fromInput($input);
        if ($human === null) {
            return $this->render($player, ['error' => 'Select a valid move.'], 422);
        }

        $computer = $this->moves->next();

        return $this->render($player, [
            'human' => $human,
            'computer' => $computer,
            'outcome' => $this->judge->judge($human, $computer),
        ]);
    }

    private function render(string $player, array $round, int $status = 200): Response
    {
        return Response::html($this->view->page('game', 'Rock Paper Scissors', [
            'player' => $player,
            'csrfToken' => $this->csrf->token(),
            'round' => $round,
        ]), $status);
    }
}
