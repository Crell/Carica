<?php

declare(strict_types=1);

namespace Crell\Carica;

use Closure;
use Psr\Container\ContainerInterface;

/**
 * Normalizes various callable forms into a Closure.
 *
 * This utility is provided to router implementations so that they can
 * more easily convert whatever their found data is into a Closure,
 * which is what is required by RouteSuccess.
 */
readonly class CallableNormalizer
{
    public function __construct(private ?ContainerInterface $container = null) {}

    /**
     * @param string|object|array $callable
     *   The callable to up-convert to a Closure, for consistency.
     *   string: A class name or service name, assumed to have an __invoke() method.
     *   array: [class or service name, method].
     *   \Closure: Already done, just return.
     *   object: An object assumed to have an __invoke() method.
     *
     * A string is interpreted as a class name, or service name
     *   if a container is provided. An array is interpreted as a
     *
     * @phpstan-param class-string|object|array{class-string, string} $callable
     * @return Closure
     */
    public function normalize(string|object|array $callable): Closure
    {
        if ($callable instanceof Closure) {
            return $callable;
        }

        // Up-convert a class-string to an object.
        if (is_string($callable)) {
            $callable = $this->loadClass($callable);
        }

        // If either an object was provided or a class name on its own,
        // assume it's an invokable object.
        $method = '__invoke';

        // If it's an array, assume it's a [classname, method] pair.
        if (is_array($callable)) {
            $method = $callable[1];
            $callable = $this->loadClass($callable[0]);
        }

        return $callable->$method(...);
    }

    /**
     * Loads an object out of the container by class name, or instantiate directly if possible.
     *
     * @phpstan-param class-string $className
     */
    private function loadClass(string $className): object
    {
        if ($this->container?->has($className)) {
            return $this->container->get($className);
        }
        // If the class has required constructor params, this will error.
        return new $className();
    }
}
