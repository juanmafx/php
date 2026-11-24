<?php

declare(strict_types=1);

namespace PhpLabs\Http;

final readonly class Request
{
    public function __construct(
        public string $method,
        public string $path,
        private array $query = [],
        private array $form = [],
        public string $clientIp = 'unknown',
        public bool $https = false,
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $method = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $clientIp = is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        return new self($method, is_string($path) ? $path : '/', $_GET, $_POST, $clientIp, $https);
    }

    public function query(string $name): ?string
    {
        $value = $this->query[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    public function form(string $name): ?string
    {
        $value = $this->form[$name] ?? null;

        return is_string($value) ? $value : null;
    }
}
