<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\FakeNext;
use Crell\Carica\Router\RouteMethodNotAllowed;
use Crell\Carica\Router\RouteNotFound;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class GenericNotFoundMiddlewareTest extends TestCase
{
    public static function ignoredExamples(): \Generator
    {
        yield 'success' => [
            'request' => new ServerRequest('GET', '/foo/bar')
                ->withAttribute(RouteResult::class, new RouteSuccess(static fn() => 'ahandler')),
            'expectedStatus' => 200,
            'expectedMessage' => 'from next',
        ];
        yield 'method not allowed' => [
            'request' => new ServerRequest('GET', '/foo/bar')
                ->withAttribute(RouteResult::class, new RouteMethodNotAllowed(['POST'])),
            'expectedStatus' => 405,
            'expectedMessage' => 'from next',
        ];
        yield 'missing result' => [
            'request' => new ServerRequest('GET', '/foo/bar'),
            'expectedStatus' => 500,
            'expectedMessage' => 'no route info',
        ];
    }

    #[Test, DataProvider('ignoredExamples')]
    public function ignoredCases(
        ServerRequestInterface $request,
        int $expectedStatus,
        string $expectedMessage,
    ): void {
        $middleware = $this->makeMiddleware();

        $response = $middleware->process($request, new FakeNext());

        self::assertEquals($expectedStatus, $response->getStatusCode());
        self::assertEquals($expectedMessage, $response->getBody()->getContents());
    }

    #[Test]
    public function methodError(): void {
        $middleware = $this->makeMiddleware();

        $request = new ServerRequest('PUT', '/foo/bar')
            ->withAttribute(RouteResult::class, new RouteNotFound());

        $response = $middleware->process($request, new FakeNext());

        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals('', $response->getBody()->getContents());
    }

    private function makeMiddleware(): MiddlewareInterface
    {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        return new GenericNotFoundMiddleware($responseBuilder);
    }
}
