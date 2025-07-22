<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
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
                return new RouteSuccess(action: static fn() => 'default');
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
        self::assertEquals('default', ($result->action)());
    }

    public static function pathRouterExamples(): \Generator
    {
        yield ['method' => 'GET', 'url' => '/foo', 'expected' => 'router1'];
        yield ['method' => 'POST', 'url' => '/foo', 'expected' => 'router1'];
        yield ['method' => 'POST', 'url' => '/foo/bar', 'expected' => 'router1'];
        yield ['method' => 'POST', 'url' => '/foo/bar.php', 'expected' => 'router1'];
        yield ['method' => 'GET', 'url' => '/', 'expected' => 'default'];
        yield ['method' => 'GET', 'url' => '/baz', 'expected' => 'default'];
        yield ['method' => 'GET', 'url' => '/foobar', 'expected' => 'default'];
    }

    #[Test, TestDox('A delegated router handles the correct routes')]
    #[DataProvider('pathRouterExamples')]
    public function pathRouter(string $method, string $url, string $expected): void
    {
        $r1 = new readonly class() implements Router {
            public function route(ServerRequestInterface $request): RouteResult
            {
                return new RouteSuccess(static fn() => 'router1');
            }
        };

        $r = new DelegatingRouter($this->defaultRouter());
        $r->delegateTo('/foo', $r1);

        $result = $r->route(new ServerRequest($method, $url));

        self::assertInstanceOf(RouteSuccess::class, $result);
        self::assertEquals($expected, ($result->action)());
    }
}
