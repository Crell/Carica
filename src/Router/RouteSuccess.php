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
     * @param ?string $parsedBodyParameter
     *   The name of the parameter that should receive the parsed body. This may be provided
     *   by the routing process.  If it isn't, it can be filled in by a later
     *   process.  AN empty string means there is no body parameter. null
     *   indicates it has not yet been determined.
     */
    public function __construct(
        public \Closure $action,
        public array $arguments = [],
        public ?array $parameters = null,
        public ?string $parsedBodyParameter = null,
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
            parsedBodyParameter: $this->parsedBodyParameter,
        );
    }

    public function withParsedBodyParameter(string $paramName): self
    {
        return new self(
            action: $this->action,
            arguments: $this->arguments,
            parameters: $this->parameters,
            parsedBodyParameter: $paramName,
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
            parsedBodyParameter: $this->parsedBodyParameter,
        );
    }
}
