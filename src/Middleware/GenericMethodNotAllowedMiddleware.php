<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ResponseBuilder;
use Crell\HttpTools\Router\RouteMethodNotAllowed;
use Crell\HttpTools\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class GenericMethodNotAllowedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseBuilder $responseBuilder,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);
        if ($result instanceof RouteMethodNotAllowed) {
            if ($request->getMethod() === 'OPTIONS') {
                return $this->responseBuilder
                    ->noContent()
                    ->withHeader('allow', implode(', ', array_map(strtoupper(...), $result->allowedMethods)))
                ;
            }
            return $this->responseBuilder->methodNotAllowed($result->allowedMethods);
        }

        return $handler->handle($request);
    }
}
