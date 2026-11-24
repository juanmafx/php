<?php

declare(strict_types=1);

namespace PhpLabs\Security;

final readonly class CsrfGuard
{
    private const string SESSION_KEY = 'csrf_token';

    public function __construct(private SessionStore $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if ($token === null) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function isValid(?string $candidate): bool
    {
        $token = $this->session->get(self::SESSION_KEY);

        return $token !== null && $candidate !== null && hash_equals($token, $candidate);
    }

    public function rotate(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }
}
