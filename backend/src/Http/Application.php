<?php

declare(strict_types=1);

namespace PhpLabs\Http;

use PhpLabs\Domain\Guess\GuessEvaluator;
use PhpLabs\Domain\HashRecovery\Md5PinRecovery;
use PhpLabs\Domain\RockPaperScissors\MoveSource;
use PhpLabs\Domain\RockPaperScissors\RoundJudge;
use PhpLabs\Http\Controller\AuthController;
use PhpLabs\Http\Controller\GameController;
use PhpLabs\Http\Controller\GuessController;
use PhpLabs\Http\Controller\HashRecoveryController;
use PhpLabs\Security\CredentialVerifier;
use PhpLabs\Security\CsrfGuard;
use PhpLabs\Security\LoginThrottle;
use PhpLabs\Security\SecurityHeaders;
use PhpLabs\Security\SessionStore;
use PhpLabs\View\TemplateRenderer;

final readonly class Application
{
    private CsrfGuard $csrf;
    private array $routes;

    public function __construct(
        SessionStore $session,
        private TemplateRenderer $view,
        CredentialVerifier $credentials,
        LoginThrottle $throttle,
        MoveSource $moves,
        private string $stylesheetPath,
    ) {
        $this->csrf = new CsrfGuard($session);
        $guess = new GuessController($view, new GuessEvaluator());
        $recovery = new HashRecoveryController($view, new Md5PinRecovery());
        $auth = new AuthController($view, $session, $this->csrf, $credentials, $throttle);
        $game = new GameController($view, $session, $this->csrf, new RoundJudge(), $moves);

        $this->routes = [
            '/' => ['GET' => $this->home(...)],
            '/guess' => ['GET' => $guess->show(...)],
            '/hash-recovery' => ['GET' => $recovery->show(...)],
            '/login' => ['GET' => $auth->show(...), 'POST' => $auth->login(...)],
            '/game' => ['GET' => $game->show(...), 'POST' => $game->play(...)],
            '/logout' => ['POST' => $auth->logout(...)],
            '/assets/app.css' => ['GET' => $this->stylesheet(...)],
        ];
    }

    public function handle(Request $request): Response
    {
        return $this->dispatch($request)->withHeaders(SecurityHeaders::for($request->https));
    }

    private function dispatch(Request $request): Response
    {
        $handlers = $this->routes[$request->path] ?? null;
        if ($handlers === null) {
            return Response::text('Not Found', 404);
        }

        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $handler = $handlers[$method] ?? null;
        if ($handler === null) {
            return Response::text('Method Not Allowed', 405)->withHeaders(['Allow' => implode(', ', array_keys($handlers))]);
        }
        if ($method === 'POST' && !$this->csrf->isValid($request->form('csrf_token'))) {
            return Response::text('Invalid CSRF token', 403);
        }

        return $handler($request);
    }

    private function home(Request $request): Response
    {
        return Response::html($this->view->page('home', 'PHP Labs'));
    }

    private function stylesheet(Request $request): Response
    {
        return new Response(200, [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ], (string) file_get_contents($this->stylesheetPath));
    }
}
