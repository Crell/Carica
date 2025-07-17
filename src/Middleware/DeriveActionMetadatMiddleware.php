<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ParsedBody;
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
class DeriveActionMetadatMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);

        if (($result instanceof RouteSuccess)) {
            if ($result->parameters === null) {
                $parameters = $this->deriveParameters($result->action);
                $result = $result->withParams($parameters);
            }
            if ($result->parsedBodyParameter === null) {
                $bodyParam = $this->deriveParsedBodyParam($result->action);
                $result = $result->withParsedBodyParameter($bodyParam);
            }
            $request = $request->withAttribute(RouteResult::class, $result);
        }

        return $handler->handle($request);
    }

    private function deriveParsedBodyParam(\Closure $action): string
    {
        $rParams = new \ReflectionFunction($action)->getParameters();

        $getAttribute = static fn (\ReflectionParameter $rParam)
            => ($rParam->getAttributes(ParsedBody::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) !== null;

        /** @var ?\ReflectionParameter $rBodyParam */
        $rBodyParam = array_find($rParams, $getAttribute);

        return $rBodyParam?->getName() ?? '';
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
