<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterMiddlewareTest extends TestCase
{
    private function successRouter(): Router
    {
        return $this->mockRouter(new RouteSuccess(static fn() => 'success'));
    }

    private function mockRouter(RouteResult $result): Router
    {
        return new readonly class($result) implements Router {
            public function __construct(private RouteResult $result) {}

            public function route(ServerRequestInterface $request): RouteResult
            {
                return $this->result;
            }
        };
    }

    #[Test, TestDox('A successful route is resolved and passed to the next middleware')]
    public function basicRoutingSuccess(): void
    {
        $request = new ServerRequest('GET', '/foo');

        $middleware = new RouterMiddleware($this->successRouter());
        $response = $middleware->process($request, new FakeNext());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('from next', $response->getBody()->getContents());
    }

    #[Test, TestDox('A missing route with no handler passes on to $next')]
    public function basicRoutingNotFound(): void
    {
        $request = new ServerRequest('GET', '/foo');

        $router = $this->mockRouter(new RouteNotFound());

        $middleware = new RouterMiddleware($router);
        $response = $middleware->process($request, new FakeNext());

        self::assertEquals('from next', $response->getBody()->getContents());
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test, TestDox('A missing route with a handler calls the handler')]
    public function handlerRoutingNotFound(): void
    {
        $request = new ServerRequest('GET', '/foo');
        $next = new FakeNext();
        $router = $this->mockRouter(new RouteNotFound());

        $notFoundHandler = new readonly class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404, body: 'from handler');
            }
        };

        $middleware = new RouterMiddleware($router, notFoundHandler: $notFoundHandler);
        $response = $middleware->process($request, $next);

        self::assertNull($next->request);
        self::assertEquals('from handler', $response->getBody()->getContents());
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test, TestDox('A disallowed route with no handler passes on to $next')]
    public function basicRoutingMethodNotAllowed(): void
    {
        $request = new ServerRequest('GET', '/foo');

        $router = $this->mockRouter(new RouteMethodNotAllowed(['POST']));

        $middleware = new RouterMiddleware($router);
        $response = $middleware->process($request, new FakeNext());

        self::assertEquals('from next', $response->getBody()->getContents());
        self::assertEquals(405, $response->getStatusCode());
    }

    #[Test, TestDox('A disallowed route with a handler calls the handler')]
    public function handlerMethodNotAllowed(): void
    {
        $request = new ServerRequest('GET', '/foo');
        $next = new FakeNext();
        $router = $this->mockRouter(new RouteMethodNotAllowed(['POST']));

        $methodNotAllowedHandler = new readonly class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(405, body: 'from handler');
            }
        };

        $middleware = new RouterMiddleware($router, methodNotAllowedHandler: $methodNotAllowedHandler);
        $response = $middleware->process($request, $next);

        self::assertNull($next->request);
        self::assertEquals('from handler', $response->getBody()->getContents());
        self::assertEquals(405, $response->getStatusCode());
    }

}
