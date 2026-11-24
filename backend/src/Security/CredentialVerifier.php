<?php

declare(strict_types=1);

namespace PhpLabs\Security;

use SensitiveParameter;

final readonly class CredentialVerifier
{
    private const int MAX_PASSWORD_BYTES = 4096;
    private const int MAX_BCRYPT_BYTES = 72;
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

    public function verify(#[SensitiveParameter] string $password): bool
    {
        if (!$this->isConfigured() || $this->passwordHash === null || strlen($password) > self::MAX_PASSWORD_BYTES) {
            return false;
        }
        if (str_contains($password, "\0")) {
            return false;
        }
        if (password_get_info($this->passwordHash)['algoName'] === 'bcrypt' && strlen($password) > self::MAX_BCRYPT_BYTES) {
            return false;
        }

        return password_verify($password, $this->passwordHash);
    }
}
