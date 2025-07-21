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
}
