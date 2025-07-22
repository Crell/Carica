<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Crell\HttpTools\StackMiddlewareKernel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class AdditionalMiddlewareMiddleware implements MiddlewareInterface
{
    public function __construct(private ?ContainerInterface $container = null) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);

        if ($result instanceof RouteSuccess && $result->actionDef?->additionalMiddleware) {
            // @phpstan-ignore argument.type (No idea why array_map() won't accept a more precise callback.)
            $middlewares = array_map($this->loadClass(...), $result->actionDef->additionalMiddleware);
            return new StackMiddlewareKernel($handler, $middlewares)->handle($request);
        }

        return $handler->handle($request);
    }

    /**
     * Loads an object out of the container by class name, or instantiate directly if possible.
     *
     * @phpstan-param class-string $className
     */
    private function loadClass(string $className): MiddlewareInterface
    {
        if ($this->container?->has($className)) {
            return $this->container->get($className);
        }
        // If the class has required constructor params, this will error.
        /** @var MiddlewareInterface */
        return new $className();
    }
}
