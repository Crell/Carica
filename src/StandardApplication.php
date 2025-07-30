<?php

declare(strict_types=1);


namespace Crell\Carica;

use Crell\AttributeUtils\FuncAnalyzer;
use Crell\AttributeUtils\FunctionAnalyzer;
use Crell\AttributeUtils\MemoryCacheFunctionAnalyzer;
use Crell\Carica\Middleware\AdditionalMiddlewareMiddleware;
use Crell\Carica\Middleware\CacheHeaderMiddleware;
use Crell\Carica\Middleware\DefaultContentTypeMiddleware;
use Crell\Carica\Middleware\DeriveActionMetadataMiddleware;
use Crell\Carica\Middleware\EnforceHeadMiddleware;
use Crell\Carica\Middleware\ExceptionCatcherMiddleware;
use Crell\Carica\Middleware\GenericMethodNotAllowedMiddleware;
use Crell\Carica\Middleware\GenericNotFoundMiddleware;
use Crell\Carica\Middleware\NormalizeArgumentTypesMiddleware;
use Crell\Carica\Middleware\ParsedBodyMiddleware;
use Crell\Carica\Middleware\QueryParametersMiddleware;
use Crell\Carica\Router\ActionDispatcher;
use Crell\Carica\Router\ActionResultRenderer;
use Crell\Carica\Router\JsonResultRenderer;
use Crell\Carica\Router\Router;
use Crell\Carica\Router\RouterMiddleware;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * A standard-configuration pre-packaged application pipeline.
 *
 * This application should be usable on its own in a pinch, however,
 * its primary purpose is to be an example of how one would wire a
 * middleware stack together, and the recommended order.  In a larger
 * application, all of this should be configured through your DI Container
 * instead of hard coded here.
 *
 * If not configured otherwise, it will assume all requests are JSON, both
 * in and out.
 *
 * If you want a custom configuration of middleware, feel free to copy-paste
 * this class and add/remove middleware as you see fit.
 */
readonly class StandardApplication implements RequestHandlerInterface
{
    private RequestHandlerInterface $handler;

    /**
     * Calling this constructor using named arguments is strongly recommended.
     *
     * @param ResponseFactoryInterface $responseFactory
     *   A PSR-17 response factory.
     * @param StreamFactoryInterface $streamFactory
     *   A PSR-17 stream factory.
     * @param Router $router
     *   A Carica router instance.
     * @param LoggerInterface|null $logger
     *   A logger to which errors should be logged.
     * @param array<class-string, ParameterLoader> $parameterLoaders
     *   Any parameter loaders that should be configured.
     * @param ?ActionResultRenderer $resultRenderer
     *   A renderer to handle action results that are not yet responses.
     *   If not specified, a standard "turn everything into JSON" one will be used.
     * @param Serde $serde
     *   A Serde instance, for use in parsing the body of an HTTP request and generating
     *   a response.
     * @param FunctionAnalyzer $analyzer
     *   An AttributeUtils function analyzer instance, for deriving action
     *   metadata if it is not already provided by the router.
     * @param ContainerInterface|null $container
     *   A container from which to load additional middleware, if requested.
     *   If not specified, only dependency-free action-specific middleware can be used.
     * @param bool $debug
     *   If true, any uncaught exception will return an HTTP message containing information
     *   about the exception.  If false, it will be silent.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        Router $router,
        ?LoggerInterface $logger = null,
        array $parameterLoaders = [],
        ?ActionResultRenderer $resultRenderer = null,
        Serde $serde = new SerdeCommon(),
        FunctionAnalyzer $analyzer = new MemoryCacheFunctionAnalyzer(new FuncAnalyzer()),
        ?ContainerInterface $container = null,
        string $defaultContentType = 'application/json',
        bool $debug = false,
    ) {
        $responseBuilder = new ResponseBuilder($responseFactory, $streamFactory);
        $resultRenderer ??= new JsonResultRenderer($responseBuilder, $serde);
        $dispatcher = new ActionDispatcher($resultRenderer, $logger);

        $this->handler = new StackMiddlewareKernel($dispatcher, [
            new ExceptionCatcherMiddleware($responseBuilder, $logger, $debug),
            new DefaultContentTypeMiddleware($defaultContentType),
            new CacheHeaderMiddleware(),
            new EnforceHeadMiddleware($streamFactory),
            new RouterMiddleware($router),
            new GenericNotFoundMiddleware($responseBuilder),
            new GenericMethodNotAllowedMiddleware($responseBuilder),
            new DeriveActionMetadataMiddleware($analyzer),
            new QueryParametersMiddleware(),
            new NormalizeArgumentTypesMiddleware($responseBuilder, $parameterLoaders),
            new ParsedBodyMiddleware($responseBuilder, [
                new SerdeBodyParser($serde),
            ]),
            new AdditionalMiddlewareMiddleware($container),
        ]);

    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handler->handle($request);
    }
}
