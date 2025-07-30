<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Crell\Carica\ActionMetadata;

/**
 * A serializable definition of a route result, with the action definition pre-filled.
 *
 * @codeCoverageIgnore
 */
readonly class RouteDefinition
{
    /**
     * @param class-string|array{class-string, string} $action
     *   
     * @param array<string, mixed> $extraArguments
     */
    public function __construct(
        public string|array $action,
        public ActionMetadata $actionDef,
        public array $extraArguments = [],
    ) {}
}
