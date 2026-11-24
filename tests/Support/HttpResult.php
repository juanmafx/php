<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Support;

final readonly class HttpResult
{
    public function __construct(public int $status, public array $headers, public string $body)
    {
    }

    public function setCookieHeader(string $cookieName): string
    {
        foreach ($this->headers as $header) {
            if (is_string($header) && stripos($header, 'Set-Cookie: ' . $cookieName . '=') === 0) {
                return $header;
            }
        }

        return '';
    }
}
