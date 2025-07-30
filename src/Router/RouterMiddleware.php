<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A simple router bridge to write routing results into the request attributes.
 *
 * You may provide custom handlers for route-not-found and method-not-allowed
 * cases, or leave that to be handled by later middleware stages.
 */
readonly class RouterMiddleware implements MiddlewareInterface
{
    /**
     * @param Router $router
     *   The Router to use.
     * @param RequestHandlerInterface|null $notFoundHandler
     *   If specified, a RouteNotFound result will cause the request
     *   to be be passed to this handler, and its result returned.
     *   If not, it is up to later middleware stages to handle that case.
     * @param RequestHandlerInterface|null $methodNotAllowedHandler
     *   If specified, a RouteMethodNotAllowed result will cause the request
     *   to be be passed to this handler, and its result returned.
     *   If not, it is up to later middleware stages to handle that case.
     */
    public function __construct(
        private Router $router,
        private ?RequestHandlerInterface $notFoundHandler = null,
        private ?RequestHandlerInterface $methodNotAllowedHandler = null,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->router->route($request);
        $request = $request->withAttribute(RouteResult::class, $result);

        if ($result instanceof RouteNotFound && $this->notFoundHandler !== null) {
            return $this->notFoundHandler->handle($request);
        }

        if ($result instanceof RouteMethodNotAllowed && $this->methodNotAllowedHandler !== null) {
            return $this->methodNotAllowedHandler->handle($request);
        }

        return $handler->handle($request);
    }
}
