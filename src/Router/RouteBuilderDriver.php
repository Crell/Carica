<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

interface RouteBuilderDriver
{
    /**
     * @param string[] $httpMethod
     *   A list of the HTTP methods to which this route should apply.
     * @param string $route
     *   The route string, including placeholders.
     * @param RouteDefinition $routeDef
     *   The Carica route definition to save for this route.
     */
    public function addRoute(array $httpMethod, string $route, RouteDefinition $routeDef): static;
}
