<?php

declare(strict_types=1);

namespace PhpLabs\Security;

interface SessionStore
{
    public function get(string $key): ?string;

    public function set(string $key, string $value): void;

    public function remove(string $key): void;

    public function regenerate(): void;

    public function destroy(): void;
}
