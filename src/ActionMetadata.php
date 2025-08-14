<?php

declare(strict_types=1);

namespace Crell\Carica;

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

    /**
     * A map of parameters that should receive a file upload.
     *
     * The key is the parameter name, the value is the file path tree.
     *
     * @see File
     *
     * @var array<string, string[]>
     */
    public array $uploadedFileParameters { get; }

    /**
     * A list of additional middleware that should be called on this action.
     *
     * @var array<string|class-string>
     */
    public array $additionalMiddleware { get; }

    /**
     * An optional user authentication definition object.
     *
     * It is up to an appropriate middleware to know how to handle different
     * authentication implementations.  Such a middleware SHOULD usually run
     * before routing.
     */
    public ?UserAuthentication $authentication { get; }

    /**
     * An optional user authorization definition object.
     *
     * It is up to an appropriate middleware to know how to handle different
     * authorization implementations.  Such a middleware MUST run after
     * routing, so that per-route access can be incorporated.
     */
    public ?UserAuthorization $authorization { get; }

}
