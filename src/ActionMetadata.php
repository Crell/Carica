<?php

declare(strict_types=1);

namespace Crell\HttpTools;

interface ActionMetadata
{
    /**
     * A map of the parameters of an action.
     *
     * The key is the name, the value is its type. This may be provided by the
     * routing process.
     *
     * @var array<string, string>
     */
    public array $parameterTypes { get; }

    /**
     * The name of the parameter that should receive the parsed body.
     *
     */
    public? string $parsedBodyParameter { get; }


    public ?string $requestParameter { get; }
}
