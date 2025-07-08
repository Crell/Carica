<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ResponseBuilder;
use Crell\HttpTools\Router\FakeNext;
use Crell\HttpTools\Router\RouteMethodNotAllowed;
use Crell\HttpTools\Router\RouteNotFound;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class GenericMethodNotAllowedMiddlewareTest extends TestCase
{
    public static function ignoredExamples(): \Generator
    {
        yield 'success' => [
            'request' => new ServerRequest('GET', '/foo/bar')
                ->withAttribute(RouteResult::class, new RouteSuccess(static fn() => 'ahandler')),
            'expectedStatus' => 200,
            'expectedMessage' => 'from next',
        ];
        yield 'not found' => [
            'request' => new ServerRequest('GET', '/foo/bar')
                ->withAttribute(RouteResult::class, new RouteNotFound()),
            'expectedStatus' => 404,
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
            ->withAttribute(RouteResult::class, new RouteMethodNotAllowed(['GET', 'POST']));

        $response = $middleware->process($request, new FakeNext());

        self::assertEquals(405, $response->getStatusCode());
        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals('GET, POST', $response->getHeaderLine('allow'));
    }

    #[Test]
    public function optionsCase(): void {
        $middleware = $this->makeMiddleware();

        $request = new ServerRequest('OPTIONS', '/foo/bar')
            ->withAttribute(RouteResult::class, new RouteMethodNotAllowed(['GET', 'POST']));

        $response = $middleware->process($request, new FakeNext());

        self::assertEquals(204, $response->getStatusCode());
        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals('GET, POST', $response->getHeaderLine('allow'));
    }

    private function makeMiddleware(): MiddlewareInterface
    {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        return new GenericMethodNotAllowedMiddleware($responseBuilder);
    }
}
