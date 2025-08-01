<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\FakeNext;
use Crell\Carica\ParsedBody;
use Crell\Carica\Point;
use Crell\Carica\RequestAttribute;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
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
            'expectedBodyParameter' => null,
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

        yield 'derive request attribute parameters' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(fn(#[RequestAttribute] string $dummy, string $name) => $dummy),
            'expectedParameters' => ['dummy' => 'string', 'name' => 'string'],
            'expectedRequestAttributes' => ['dummy' => 'dummy'],
        ];

        yield 'derive request attribute parameter with name override' => [
            'uri' => '/foo/bar',
            'routeResult' => new RouteSuccess(fn(#[RequestAttribute('dummy')] string $alternate, string $name) => $alternate),
            'expectedParameters' => ['alternate' => 'string', 'name' => 'string'],
            'expectedRequestAttributes' => ['alternate' => 'dummy'],
        ];
    }

    /**
     * @param array<string, string> $expectedParameters
     * @param array<string, string> $expectedRequestAttributes
     */
    #[Test, DataProvider('deriveMetadataExamples')]
    public function deriveMetadata(
        string $uri,
        RouteResult $routeResult,
        array $expectedParameters = [],
        ?string $expectedBodyParameter = null,
        array $expectedRequestAttributes = [],
    ): void
    {
        $middleware = new DeriveActionMetadataMiddleware();

        $request = new ServerRequest('GET', $uri)
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute('dummy', 'value')
        ;

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        self::assertNotNull($fakeNext->request);
        /** @var RouteSuccess $updatedResult */
        $updatedResult = $fakeNext->request->getAttribute(RouteResult::class);

        self::assertEquals($expectedParameters, $updatedResult->actionDef?->parameterTypes);
        self::assertEquals($expectedBodyParameter, $updatedResult->actionDef?->parsedBodyParameter);
        self::assertEquals($expectedRequestAttributes, $updatedResult->actionDef?->requestAttributes);
    }
}
