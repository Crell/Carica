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
 * Checks that all arguments that have a matching parameter match the type of the parameter.
 *
 * If possible, it will convert them.  (Eg, from a numeric string to an int.)
 */
class NormalizeScalarArgumentsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);
        if ($result instanceof RouteSuccess && $result->parameters !== null) {
            $newArgs = $this->normalizeValues($result->arguments, $result->parameters);
            $result = $result->withAddedArgs($newArgs);
            $request = $request->withAttribute(RouteResult::class, $result);
        }

        return $handler->handle($request);
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, string> $parameters
     * @return array<string, mixed>
     */
    private function normalizeValues(array $arguments, array $parameters): array
    {
        $newArgs = [];
        foreach (array_intersect_key($arguments, $parameters) as $k => $val) {
            $replacement = match ($parameters[$k]) {
                'string' => $val,
                'float' => is_numeric($val)
                    ? (float)$val
                    : throw new \Exception('Todo: Better error'),
                'int' => (is_numeric($val) && floor((float)$val) === (float)$val)
                    ? (int)$val
                    : throw new \Exception('Todo: Better error'),
                'bool' => in_array(strtolower($val), [1, '1', 'true', 'yes', 'on'], false),
                // If the parameter type is an array or object, assume someone else will handle it.
                default => null,
            };
            if ($replacement !== null) {
                $newArgs[$k] = $replacement;
            }
        }
        return $newArgs;
    }
}
