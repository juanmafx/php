<?php

declare(strict_types=1);

namespace PhpLabs\Http\Controller;

use PhpLabs\Http\Request;
use PhpLabs\Http\Response;
use PhpLabs\Security\CredentialVerifier;
use PhpLabs\Security\CsrfGuard;
use PhpLabs\Security\LoginThrottle;
use PhpLabs\Security\SessionStore;
use PhpLabs\View\TemplateRenderer;

final readonly class AuthController
{
    public const string PLAYER_KEY = 'player_name';
    private const int MAX_NAME_LENGTH = 64;

    public function __construct(
        private TemplateRenderer $view,
        private SessionStore $session,
        private CsrfGuard $csrf,
        private CredentialVerifier $credentials,
        private LoginThrottle $throttle,
    ) {
    }

    public function show(Request $request): Response
    {
        return $this->form($this->credentials->isConfigured() ? null : 'Login is disabled until a password hash is configured.');
    }

    public function login(Request $request): Response
    {
        if ($request->form('cancel') !== null) {
            return Response::redirect('/');
        }
        if (!$this->credentials->isConfigured()) {
            return $this->form('Login is disabled until a password hash is configured.', 503);
        }
        if ($this->throttle->isBlocked($request->clientIp)) {
            return $this->form('Too many failed attempts. Try again later.', 429);
        }

        $name = trim($request->form('who') ?? '');
        $password = $request->form('pass') ?? '';

        if ($name === '' || $password === '') {
            return $this->form('User name and password are required.', 422);
        }
        if (mb_strlen($name, 'UTF-8') > self::MAX_NAME_LENGTH || !mb_check_encoding($name, 'UTF-8')) {
            return $this->form('User name is too long or invalid.', 422);
        }
        if (!$this->credentials->verify($password)) {
            $this->throttle->recordFailure($request->clientIp);

            return $this->form('Incorrect credentials.', 401);
        }

        $this->throttle->reset($request->clientIp);
        $this->session->regenerate();
        $this->csrf->rotate();
        $this->session->set(self::PLAYER_KEY, $name);

        return Response::redirect('/game');
    }

    public function logout(Request $request): Response
    {
        $this->session->destroy();

        return Response::redirect('/');
    }

    private function form(?string $error, int $status = 200): Response
    {
        return Response::html($this->view->page('login', 'Log In', [
            'error' => $error,
            'csrfToken' => $this->csrf->token(),
        ]), $status);
    }
}
