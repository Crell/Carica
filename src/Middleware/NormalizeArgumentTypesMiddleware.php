<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ParameterLoader;
use Crell\HttpTools\ResponseBuilder;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks that all arguments that have a matching parameter match the type of the parameter.
 *
 * If possible, it will convert them.  (Eg, from a numeric string to an int.)
 */
class NormalizeArgumentTypesMiddleware implements MiddlewareInterface
{
    /**
     * @param array<class-string, ParameterLoader> $loaders
     */
    public function __construct(
        private readonly ResponseBuilder $responseBuilder,
        protected array $loaders = [],
    ) {}

    /**
     * @phpstan-param class-string $class
     * @return $this
     */
    public function addLoader(string $class, ParameterLoader $loader): self
    {
        $this->loaders[$class] = $loader;
        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);
        if ($result instanceof RouteSuccess && $result->actionDef?->parameterTypes !== null) {
            $newArgs = [];
            foreach (array_intersect_key($result->arguments, $result->actionDef->parameterTypes) as $name => $val) {
                $normalizedValue = $this->normalizeValue($val, $result->actionDef->parameterTypes[$name]);
                if ($normalizedValue instanceof CannotNormalizeValue) {
                    // @todo Make this pluggable?
                    return $this->responseBuilder->badRequest(sprintf('The %s parameter expects a %s. %s provided.', $name, $result->actionDef->parameterTypes[$name], get_debug_type($val)));
                }
                $newArgs[$name] = $normalizedValue;
            }
            $result = $result->withAddedArgs($newArgs);
            $request = $request->withAttribute(RouteResult::class, $result);
        }

        return $handler->handle($request);
    }

    private function normalizeValue(mixed $value, string $type): mixed
    {
        if ($type === 'string') {
            return (string)$value;
        }
        if ($type === 'float') {
            if (is_numeric($value)) {
                return (float)$value;
            }
            return new CannotNormalizeValue();
        }

        if ($type === 'int') {
            if ((is_numeric($value) && floor((float)$value) === (float)$value)) {
                return (int)$value;
            }
            return new CannotNormalizeValue();
        }

        // Allow various standard boolean-esque terms to fold to boolean.
        if ($type === 'bool') {
            if (is_string($value)) {
                $value = strtolower($value);
            }
            return match ($value) {
                1, '1', 'true', 'yes', 'on' => true,
                0, '0', 'false', 'no', 'off' => false,
                default => new CannotNormalizeValue(),
            };
        }

        if (class_exists($type) || interface_exists($type)) {
            foreach ($this->loaders as $class => $loader) {
                if (is_a($class, $type, true)) {
                    $loaded = $loader->load($value, $type);
                    if ($loaded !== null) {
                        return $loaded;
                    }
                }
            }
            return new CannotNormalizeValue();
        }

        return $value;
    }
}
