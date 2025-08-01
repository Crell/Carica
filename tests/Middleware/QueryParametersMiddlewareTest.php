<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\FakeNext;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class QueryParametersMiddlewareTest extends TestCase
{

    public static function queryMappingExamples(): \Generator
    {
        yield 'no query' => [
            'request' => new ServerRequest('GET', '/foo/bar')
                ->withAttribute(RouteResult::class, new RouteSuccess(static fn() => 'no query')),
            'expectedStatus' => 200,
            'expectedMessage' => 'from next',
            'expectedArgs' => [],
        ];
        yield 'string query' => [
            'request' => new ServerRequest('GET', '/foo/bar?baz=beep')
                ->withAttribute(RouteResult::class, new RouteSuccess(static fn() => 'string query')),
            'expectedStatus' => 200,
            'expectedMessage' => 'from next',
            'expectedArgs' => ['baz' => 'beep']
        ];
        yield 'int query' => [
            'request' => new ServerRequest('GET', '/foo/bar?baz=1')
                ->withAttribute(RouteResult::class, new RouteSuccess(static fn() => 'string query')),
            'expectedStatus' => 200,
            'expectedMessage' => 'from next',
            'expectedArgs' => ['baz' => 1],
        ];
    }

    /**
     * @param array<string, int|string> $expectedArgs
     */
    #[Test, DataProvider('queryMappingExamples')]
    public function queryMapping(
        ServerRequestInterface $request,
        int $expectedStatus,
        string $expectedMessage,
        array $expectedArgs,
    ): void {
        $middleware = new QueryParametersMiddleware();

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        $result = $fakeNext->request?->getAttribute(RouteResult::class);
        self::assertInstanceOf(RouteSuccess::class, $result);

        self::assertEquals($expectedArgs, $result->arguments);
        self::assertEquals($expectedStatus, $response->getStatusCode());
        self::assertEquals($expectedMessage, $response->getBody()->getContents());
    }
}
