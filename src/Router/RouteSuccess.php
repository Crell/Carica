<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

readonly class RouteSuccess implements RouteResult
{
    /**
     * @param array<string, mixed> $arguments
     *   The placeholder arguments extracted from the route path.
     *
     */
    public function __construct(
        public string|\Closure $action,
        public array $arguments = [],
    ) {}

    /**
     * @param array<string, mixed> $args
     */
    public function withAddedArgs(array $args): self
    {
        return new self(
            action: $this->action,
            arguments: $args + $this->arguments,
        );
    }
}
