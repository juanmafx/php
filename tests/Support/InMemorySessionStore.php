<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Support;

use PhpLabs\Security\SessionStore;

final class InMemorySessionStore implements SessionStore
{
    public int $regenerations = 0;
    private array $values = [];

    public function get(string $key): ?string
    {
        $value = $this->values[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function set(string $key, string $value): void
    {
        $this->values[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }

    public function regenerate(): void
    {
        $this->regenerations++;
    }

    public function destroy(): void
    {
        $this->values = [];
    }
}
