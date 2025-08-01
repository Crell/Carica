<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

class FakeRouteBuilderDriver implements RouteBuilderDriver
{
    /**
     * @var array<array{method: array<string>, route: string, routeDef: RouteDefinition}|array{method: string}>
     */
    private(set) array $added = [];

    public function addRoute(array $httpMethod, string $route, RouteDefinition $routeDef): static
    {
        $this->added[] = [
            'method' => $httpMethod,
            'route' => $route,
            'routeDef' => $routeDef,
        ];
        return $this;
    }
}
