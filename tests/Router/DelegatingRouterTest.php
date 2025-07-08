<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class DelegatingRouterTest extends TestCase
{
    private function defaultRouter(): Router
    {
        return new readonly class implements Router {

            public function route(ServerRequestInterface $request): RouteResult
            {
                return new RouteSuccess(action: 'default');
            }
        };
    }

    #[Test, TestDox('With just a default router, the default is always reached')]
    #[TestWith(['method' => 'GET', 'url' => '/foo'])]
    #[TestWith(['method' => 'POST', 'url' => '/foo'])]
    #[TestWith(['method' => 'GET', 'url' => '/'])]
    public function defaultRouterReached(string $method, string $url): void
    {
        $r = new DelegatingRouter($this->defaultRouter());

        $result = $r->route(new ServerRequest($method, $url));

        self::assertInstanceOf(RouteSuccess::class, $result);
        self::assertIsString($result->action);
        self::assertEquals('default', $result->action);
    }

    #[Test, TestDox('A delegated router handles the correct routes')]
    #[TestWith(['method' => 'GET', 'url' => '/foo', 'router1'])]
    #[TestWith(['method' => 'POST', 'url' => '/foo', 'router1'])]
    #[TestWith(['method' => 'POST', 'url' => '/foo/bar', 'router1'])]
    #[TestWith(['method' => 'POST', 'url' => '/foo/bar.php', 'router1'])]
    #[TestWith(['method' => 'GET', 'url' => '/', 'default'])]
    #[TestWith(['method' => 'GET', 'url' => '/baz', 'default'])]
    #[TestWith(['method' => 'GET', 'url' => '/foobar', 'default'])]
    public function pathRouter(string $method, string $url, string $expected): void
    {
        $r1 = new readonly class() implements Router {
            public function route(ServerRequestInterface $request): RouteResult
            {
                return new RouteSuccess('router1');
            }
        };

        $r = new DelegatingRouter($this->defaultRouter());
        $r->delegateTo('/foo', $r1);

        $result = $r->route(new ServerRequest($method, $url));

        self::assertInstanceOf(RouteSuccess::class, $result);
        self::assertIsString($result->action);
        self::assertEquals($expected, $result->action);
    }
}
