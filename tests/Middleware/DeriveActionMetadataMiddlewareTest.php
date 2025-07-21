<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ParsedBody;
use Crell\HttpTools\Point;
use Crell\HttpTools\Router\FakeNext;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DeriveActionMetadataMiddlewareTest extends TestCase
{

    public static function deriveMetadataExamples(): \Generator
    {
        yield 'derive simple parameters' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(fn(string $a, int $b) => $a . $b),
            'expectedParameters' => ['a' => 'string', 'b' => 'int'],
            'expectedBodyParameter' => '',
        ];

        yield 'derive body parameter' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(fn(#[ParsedBody] Point $point) => $point->x),
            'expectedParameters' => ['point' => Point::class],
            'expectedBodyParameter' => 'point',
        ];

        yield 'derive both regular params and body parameter' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(fn(#[ParsedBody] Point $point, string $name) => $point->x),
            'expectedParameters' => ['point' => Point::class, 'name' => 'string'],
            'expectedBodyParameter' => 'point',
        ];
    }

    /**
     * @param array<string, string> $expectedParameters
     */
    #[Test, DataProvider('deriveMetadataExamples')]
    public function deriveMetadata(
        string $uri,
        RouteResult $routeResult,
        array $expectedParameters,
        string $expectedBodyParameter,
    ): void
    {
        $middleware = new DeriveActionMetadataMiddleware();

        $request = new ServerRequest('GET', $uri)
            ->withAttribute(RouteResult::class, $routeResult)
        ;

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        self::assertNotNull($fakeNext->request);
        /** @var RouteSuccess $updatedResult */
        $updatedResult = $fakeNext->request->getAttribute(RouteResult::class);

        self::assertEquals($expectedParameters, $updatedResult->actionDef?->parameterTypes);
        self::assertEquals($expectedBodyParameter, $updatedResult->actionDef?->parsedBodyParameter);
    }
}
