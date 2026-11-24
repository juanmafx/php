<?php

declare(strict_types=1);

namespace PhpLabs\Http;

final readonly class Response
{
    public function __construct(public int $status, public array $headers, public string $body)
    {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=utf-8', 'Cache-Control' => 'no-store'], $body);
    }

    public static function text(string $body, int $status): self
    {
        return new self($status, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'no-store'], $body);
    }

    public static function redirect(string $path): self
    {
        return new self(303, ['Location' => $path, 'Cache-Control' => 'no-store'], '');
    }

    public function withHeaders(array $headers): self
    {
        return new self($this->status, $this->headers + $headers, $this->body);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            if (is_string($name) && is_string($value)) {
                header($name . ': ' . $value);
            }
        }
        echo $this->body;
    }
}
