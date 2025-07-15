<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds query parameters to the list of available arguments to pass to the action.
 */
class QueryParametersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);
        if ($result instanceof RouteSuccess && $query = $request->getQueryParams()) {
            $request = $request->withAttribute(RouteResult::class, $result->withAddedArgs($query));
        }

        return $handler->handle($request);
    }
}
