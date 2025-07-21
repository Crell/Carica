<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Crell\HttpTools\ActionMetadata;

readonly class RouteSuccess implements RouteResult
{
    /**
     * @param array<string, mixed> $arguments
     *   The placeholder arguments extracted from the route path.  The key
     *   is the placeholder name. The value is the extracted value.
     */
    public function __construct(
        public \Closure $action,
        public array $arguments = [],
        public ?ActionMetadata $actionDef = null,
    ) {}

    public function withActionDef(ActionMetadata $def): self
    {
        return new self(
            action: $this->action,
            arguments: $this->arguments,
            actionDef: $def,
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    public function withAddedArgs(array $args): self
    {
        return new self(
            action: $this->action,
            arguments: $args + $this->arguments,
            actionDef: $this->actionDef,
        );
    }

//    public function withParsedBodyParameter(string $paramName): self
//    {
//        return new self(
//            action: $this->action,
//            arguments: $this->arguments,
//            parameters: $this->parameters,
//            parsedBodyParameter: $paramName,
//        );
//    }
//
//    /**
//     * @param array<string, string> $params
//     */
//    public function withParams(array $params): self
//    {
//        return new self(
//            action: $this->action,
//            arguments: $this->arguments,
//            parameters: $params + ($this->parameters ?? []),
//            parsedBodyParameter: $this->parsedBodyParameter,
//        );
//    }
}
