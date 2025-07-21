<?php

declare(strict_types=1);

namespace Crell\HttpTools;

interface ActionMetadata
{
    /**
     * A map of the parameters of an action.
     *
     * The key is the name, the value is its type. This may be provided by the
     * routing process. If it isn't, they can be filled in by a later process. An empty array means there are no parameters.
     *    null indicates they have not been determined yet.
     *
     * @var array<string, string|null>|null
     */

    public ?array $parameterTypes { get; }

    /**
     * The name of the parameter that should receive the parsed body.
     * This may be provided by the routing process.  If it isn't, it can
     * be filled in by a later process.  AN empty string means there is
     * no body parameter. null indicates it has not yet been determined.
     */
    public? string $parsedBodyParameter { get; }


    public ?string $requestParameter { get; }
}
