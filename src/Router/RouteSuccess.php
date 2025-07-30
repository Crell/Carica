<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Crell\Carica\ActionMetadata;
use Crell\Carica\Middleware\DeriveActionMetadataMiddleware;

readonly class RouteSuccess implements RouteResult
{
    /**
     * @param array<string, mixed> $arguments
     *   The placeholder arguments extracted from the route path.  The key
     *   is the placeholder name. The value is the extracted value.
     * @param ?ActionMetadata $actionDef
     *   The metadata about the action that will be used by routing.
     *   If null, it is expected that it will be filled in by a later
     *   process.
     *
     * @see DeriveActionMetadataMiddleware
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
}
