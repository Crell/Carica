<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Crell\HttpTools\Router\Router;
use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;

readonly class FastRouteRouter implements Router
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function route(ServerRequestInterface $request): RouteResult
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        return $this->toRouteResult($routeInfo);
    }

    /**
     * @param array<mixed, mixed> $routeInfo
     */
    private function toRouteResult(array $routeInfo): RouteResult
    {
        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => new RouteNotFound(),
            Dispatcher::METHOD_NOT_ALLOWED => new RouteMethodNotAllowed($routeInfo[1]),
            Dispatcher::FOUND => new RouteSuccess($routeInfo[1], $routeInfo[2]),
            default => throw new \LogicException('It should not be possible to get here.'),
        };
    }
}
