<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\Router\FakeNext;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DeriveActionParametersMiddlewareTest extends TestCase
{
    #[Test]
    public function derivesParams(): void
    {
        $middleware = new DeriveActionParametersMiddleware();

        $result = new RouteSuccess(fn(string $a) => $a, parameters: null);
        $request = new ServerRequest('GET', '/foo/bar')
            ->withAttribute(RouteResult::class, $result)
        ;

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        self::assertNotNull($fakeNext->request);
        /** @var RouteSuccess $updatedResult */
        $updatedResult = $fakeNext->request->getAttribute(RouteResult::class);

        self::assertEquals(['a' => 'string'], $updatedResult->parameters);
    }
}
