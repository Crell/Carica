<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\AttributeUtils\FuncAnalyzer;
use Crell\AttributeUtils\FunctionAnalyzer;
use Crell\AttributeUtils\MemoryCacheFunctionAnalyzer;
use Crell\HttpTools\ActionMetadataAttribute;
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
 * If the RouteResult does not already have the action metadata, derive them.
 */
readonly class DeriveActionMetadataMiddleware implements MiddlewareInterface
{
    public function __construct(
        private FunctionAnalyzer $analyzer = new MemoryCacheFunctionAnalyzer(new FuncAnalyzer()),
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);

        if ($result instanceof RouteSuccess && $result->actionDef === null) {
            $def = $this->analyzer->analyze($result->action, ActionMetadataAttribute::class);
            $result = $result->withActionDef($def);
            $request = $request->withAttribute(RouteResult::class, $result);
        }

        return $handler->handle($request);
    }

//    /**
//     * @param \ReflectionParameter[] $rParams
//     */
//    private function deriveParsedBodyParam(\Closure $action, array $rParams): string
//    {
//        $getAttribute = static fn (\ReflectionParameter $rParam)
//            => ($rParam->getAttributes(ParsedBody::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) !== null;
//
//        /** @var ?\ReflectionParameter $rBodyParam */
//        $rBodyParam = array_find($rParams, $getAttribute);
//
//        return $rBodyParam?->getName() ?? '';
//    }
//
//    /**
//     * @param \ReflectionParameter[] $rParams
//     * @return array<string, string>
//     */
//    private function deriveParameters(\Closure $action, array $rParams): array
//    {
//        // @todo Better handle union types, which I doubt are supportable.
//        return array_combine(
//            amap(method('getName'))($rParams),
//            // @phpstan-ignore method.notFound (We're assuming only named types here, so getName() is available.)
//            amap(fn(\ReflectionParameter $r): string => $r->getType()?->getName() ?? 'mixed')($rParams),
//        );
//    }
}
