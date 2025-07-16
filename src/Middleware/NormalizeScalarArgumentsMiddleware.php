<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

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
class NormalizeScalarArgumentsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseBuilder $responseBuilder,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);
        if ($result instanceof RouteSuccess && $result->parameters !== null) {
            $newArgs = [];
            foreach (array_intersect_key($result->arguments, $result->parameters) as $name => $val) {
                $normalizedValue = $this->normalizeValue($name, $val, $result->parameters[$name]);
                if ($normalizedValue instanceof CannotNormalizeValue) {
                    // @todo Make this pluggable?
                    return $this->responseBuilder->badRequest(sprintf('The %s parameter expects a %s. %s provided.', $name, $result->parameters[$name], get_debug_type($val)));
                }
                $newArgs[$name] = $normalizedValue;
            }
            $result = $result->withAddedArgs($newArgs);
            $request = $request->withAttribute(RouteResult::class, $result);
        }

        return $handler->handle($request);
    }

    private function normalizeValue(string $name, mixed $value, string $type): mixed
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
            return (in_array(strtolower($value), [1, '1', 'true', 'yes', 'on'], false));
        }

        // @todo Put mechanism for handling upcasters here.

        return $value;
    }
}
