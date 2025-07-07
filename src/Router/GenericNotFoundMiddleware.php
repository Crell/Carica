<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Crell\HttpTools\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class GenericNotFoundMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseBuilder $responseBuilder,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute(RouteResult::class) instanceof RouteNotFound) {
            return $this->responseBuilder->notFound('');
        }

        return $handler->handle($request);
    }
}
