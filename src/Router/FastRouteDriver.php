<?php

declare(strict_types=1);


namespace Crell\Carica\Router;

use Crell\Carica\HttpMethod;
use Crell\Carica\Router\RouteBuilderDriver;
use FastRoute\RouteCollector;

readonly class FastRouteDriver implements RouteBuilderDriver
{
    public function __construct(private RouteCollector $routeCollector) {}

    public function addRoute(array $httpMethod, string $route, RouteDefinition $routeDef): static
    {
        $this->routeCollector->addRoute($httpMethod, $route, $routeDef);

        return $this;
    }
}
