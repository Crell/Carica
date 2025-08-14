<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Carica\ActionClass;
use Crell\Carica\HttpMethod;

/**
 * @todo Remove this, assuming the PreParsingRouteCollector works as intended.
 */
readonly class RouteBuilder
{
    public function __construct(
        private RouteBuilderDriver $driver,
        private ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    /**
     * @param string|HttpMethod|array<string|HttpMethod> $httpMethod
     * @param string $route
     * @param class-string|array{class-string, string} $action
     * @param array<string, scalar> $extraArguments
     * @return $this
     */
    public function route(string|HttpMethod|array $httpMethod, string $route, string|array $action, array $extraArguments = []): self
    {
        $httpMethod = $this->normalizeHttpMethods($httpMethod);

        if (is_string($action)) {
            $action = [$action, '__invoke'];
        }

        if (!class_exists($action[0])) {
            // @todo Figure out how to support classes in a container not indexed by class name.
            throw new \RuntimeException(sprintf('Class %s not found.', $action[0]));
        }

        $classDef = $this->analyzer->analyze($action[0], ActionClass::class);
        $methodDef = $classDef->methods[$action[1]] ?? throw new \RuntimeException(sprintf('Method %s of class %s not found.', $action[1], $action[0]));
        $routeDef = new RouteDefinition($action, $methodDef, $extraArguments);

        $this->driver->addRoute($httpMethod, $route, $routeDef);

        return $this;
    }

    /**
     * Adds a GET route.
     *
     * @param string $route
     * @param class-string|array{class-string, string} $action
     * @param array<string, scalar> $extraArguments
     * @return $this
     * @see self::route()
     */
    public function get(string $route, string|array $action, array $extraArguments = []): self
    {
        return $this->route(['GET'], $route, $action, $extraArguments);
    }

    /**
     * Adds a POST route.
     *
     * @param string $route
     * @param class-string|array{class-string, string} $action
     * @param array<string, scalar> $extraArguments
     * @return $this
     * @see self::route()
     */
    public function post(string $route, string|array $action, array $extraArguments = []): self
    {
        return $this->route(['POST'], $route, $action, $extraArguments);
    }

    /**
     * Adds a PUT route.
     *
     * @param string $route
     * @param class-string|array{class-string, string} $action
     * @param array<string, scalar> $extraArguments
     * @return $this
     * @see self::route()
     */
    public function put(string $route, string|array $action, array $extraArguments = []): self
    {
        return $this->route(['PUT'], $route, $action, $extraArguments);
    }

    /**
     * Adds a DELETE route.
     *
     * @param string $route
     * @param class-string|array{class-string, string} $action
     * @param array<string, scalar> $extraArguments
     * @return $this
     * @see self::route()
     */
    public function delete(string $route, string|array $action, array $extraArguments = []): self
    {
        return $this->route(['DELETE'], $route, $action, $extraArguments);
    }

    /**
     * @param string|HttpMethod|array<string|HttpMethod> $httpMethod
     * @return string[]
     *   An array of HTTP method strings
     */
    private function normalizeHttpMethods(string|HttpMethod|array $httpMethod): array
    {
        if (!is_array($httpMethod)) {
            $httpMethod = [$httpMethod];
        }
        return array_map(static fn(string|HttpMethod $m) => is_string($m) ? strtoupper($m) : $m->value, $httpMethod);
    }
}
