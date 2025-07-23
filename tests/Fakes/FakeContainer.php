<?php

declare(strict_types=1);

namespace Crell\HttpTools\Fakes;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class FakeContainer implements ContainerInterface
{
    /**
     * @phpstan-param array<string, mixed> $services
     */
    public function __construct(
        private array $services = [],
    ) {}

    public function set(string $id, mixed $value): self
    {
        $this->services[$id] = $value;
        return $this;
    }

    public function get(string $id): mixed
    {
        return $this->services[$id]
            ?? throw new class extends \Exception implements NotFoundExceptionInterface {};
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
