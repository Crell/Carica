<?php

declare(strict_types=1);

namespace Crell\HttpTools;

interface ActionMetadata
{
    /**
     * A map of the parameters of an action.
     *
     * The key is the name, the value is its type.
     *
     * @var array<string, string>
     */
    public array $parameterTypes { get; }

    /**
     * The name of the parameter that should receive the parsed body.
     *
     * null to indicate no parameter wants the parsed body.
     */
    public? string $parsedBodyParameter { get; }

    /**
     * The name of the parameter that should receive the request object.
     *
     * null to indicate no parameter wants the request object.
     */
    public ?string $requestParameter { get; }

    /**
     * A map of parameters that should receive a request attribute.
     *
     * The key is the parameter name, the value is the request attribute it should be given.
     *
     * @var array<string, string>
     */
    public array $requestAttributes { get; }
}
