<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

readonly class RouteSuccess implements RouteResult
{
    /**
     * @param array<string, mixed> $arguments
     *   The placeholder arguments extracted from the route path.  The key
     *   is the placeholder name. The value is the extracted value.
     * @param array<string, string>|null $parameters
     *   A map of the parameters of $action. The key is the name, the value is its type.
     *   This may be provided by the routing process. If it isn't, they can be
     *   filled in by a later process. An empty array means there are no parameters.
     *   null indicates they have not been determined yet.
     */
    public function __construct(
        public \Closure $action,
        public array $arguments = [],
        public ?array $parameters = null,
    ) {}

    /**
     * @param array<string, mixed> $args
     */
    public function withAddedArgs(array $args): self
    {
        return new self(
            action: $this->action,
            arguments: $args + $this->arguments,
            parameters: $this->parameters,
        );
    }

    /**
     * @param array<string, string> $params
     */
    public function withParams(array $params): self
    {
        return new self(
            action: $this->action,
            arguments: $this->arguments,
            parameters: $params + ($this->parameters ?? []),
        );
    }
}
