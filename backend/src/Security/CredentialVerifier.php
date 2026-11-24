<?php

declare(strict_types=1);

namespace PhpLabs\Security;

final readonly class CredentialVerifier
{
    private const int MAX_PASSWORD_BYTES = 4096;
    private const array ACCEPTED_ALGORITHMS = ['argon2id', 'bcrypt'];

    public function __construct(private ?string $passwordHash)
    {
    }

    public function isConfigured(): bool
    {
        if ($this->passwordHash === null || $this->passwordHash === '') {
            return false;
        }

        return in_array(password_get_info($this->passwordHash)['algoName'], self::ACCEPTED_ALGORITHMS, true);
    }

    public function verify(string $password): bool
    {
        if (!$this->isConfigured() || $this->passwordHash === null || strlen($password) > self::MAX_PASSWORD_BYTES) {
            return false;
        }

        return password_verify($password, $this->passwordHash);
    }
}
