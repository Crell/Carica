<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use FastRoute\DataGenerator\GroupCountBased as GroupGenerator;
use FastRoute\Dispatcher\GroupCountBased as GroupDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class FastRouteRouterTest extends TestCase
{

    public static function routeExamples(): \Generator
    {
        yield 'static route' => [
            'route' => '/foo/bar',
            'method' => 'GET',
            'handler' => static fn() => 'ahandler',
            'request' => new ServerRequest('GET', '/foo/bar'),
            'expectedResult' => new RouteSuccess(static fn() => 'ahandler', []),
        ];

        yield 'placeholder route' => [
            'route' => '/foo/{bar}/baz',
            'method' => 'GET',
            'handler' => static fn() => 'ahandler',
            'request' => new ServerRequest('GET', '/foo/beep/baz'),
            'expectedResult' => new RouteSuccess(static fn() => 'ahandler', ['bar' => 'beep']),
        ];
    }

    #[Test, DataProvider('routeExamples')]
    public function routeResults(
        string $route,
        string $method = 'GET',
        ?\Closure $handler = null,
        ?ServerRequestInterface $request = null,
        ?RouteResult $expectedResult = null,
    ): void
    {
        self::assertNotNull($handler);
        self::assertNotNull($request);
        self::assertNotNull($expectedResult);

        $routeCollector = new RouteCollector(new Std(), new GroupGenerator());

        $routeCollector->addRoute($method, $route, $handler);

        $dispatcher = new GroupDispatcher($routeCollector->getData());

        $router = new FastRouteRouter($dispatcher);

        $result = $router->route($request);

        self::assertEquals($expectedResult, $result);
    }
}
