<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Crell\fp\amap;
use function Crell\fp\method;

/**
 * If the RouteResult does not already have the action parameters, derive them.
 */
class DeriveActionParametersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);

        // @todo This would be a lovely pipe case in PHP 8.5. :-)
        if (($result instanceof RouteSuccess) && $result->parameters === null) {
            $parameters = $this->deriveParameters($result->action);
            $request = $request->withAttribute(RouteResult::class, $result->withParams($parameters));
        }

        return $handler->handle($request);
    }

    /**
     * @return array<string, string>
     */
    private function deriveParameters(\Closure $action): array
    {
        $rParams = new \ReflectionFunction($action)->getParameters();
        // @todo Better handle union types, which I doubt are supportable.
        return array_combine(
            amap(method('getName'))($rParams),
            // @phpstan-ignore method.notFound (We're assuming only named types here, so getName() is available.)
            amap(fn(\ReflectionParameter $r): string => $r->getType()?->getName() ?? 'mixed')($rParams),
        );
    }
}
