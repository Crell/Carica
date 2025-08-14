<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Crell\Carica\ExplicitActionMetadata;
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

        yield 'route def, placeholder route' => [
            'route' => '/foo/{name}/baz',
            'method' => 'GET',
            'handler' => new RouteDefinition([FakeActions::class, 'stringParam'], new ExplicitActionMetadata(['name' => 'string'])),
            'request' => new ServerRequest('GET', '/foo/beep/baz'),
            'expectedResult' => new RouteSuccess(new FakeActions()->stringParam(...), ['name' => 'beep']),
        ];

        yield 'invokable route def, placeholder route' => [
            'route' => '/foo/{name}/baz',
            'method' => 'GET',
            'handler' => new RouteDefinition(FakeInvokableAction::class, new ExplicitActionMetadata(['key' => 'string'])),
            'request' => new ServerRequest('GET', '/foo/beep/baz'),
            'expectedResult' => new RouteSuccess(new FakeActions()->stringParam(...), ['name' => 'beep']),
        ];

        yield 'invokable route def, placeholder route, extra args' => [
            'route' => '/foo/{name}/baz',
            'method' => 'GET',
            'handler' => new RouteDefinition(FakeInvokableAction::class, new ExplicitActionMetadata(['key' => 'string']), ['extra' => 'value']),
            'request' => new ServerRequest('GET', '/foo/beep/baz'),
            'expectedResult' => new RouteSuccess(new FakeActions()->stringParam(...), ['extra' => 'value', 'name' => 'beep']),
        ];

        yield 'invokable route def, placeholder route, extra args with overlap' => [
            'route' => '/foo/{name}/baz',
            'method' => 'GET',
            'handler' => new RouteDefinition(FakeInvokableAction::class, new ExplicitActionMetadata(['key' => 'string']), ['extra' => 'value', 'name' => 'override']),
            'request' => new ServerRequest('GET', '/foo/beep/baz'),
            'expectedResult' => new RouteSuccess(new FakeActions()->stringParam(...), ['extra' => 'value', 'name' => 'override']),
        ];

        yield 'not found' => [
            'route' => '/foo',
            'method' => 'GET',
            'handler' => new RouteDefinition(FakeInvokableAction::class, new ExplicitActionMetadata()),
            'request' => new ServerRequest('GET', '/missing'),
            'expectedResult' => new RouteNotFound(),
        ];

        yield 'method not allowed' => [
            'route' => '/foo',
            'method' => 'POST',
            'handler' => new RouteDefinition(FakeInvokableAction::class, new ExplicitActionMetadata()),
            'request' => new ServerRequest('GET', '/foo'),
            'expectedResult' => new RouteMethodNotAllowed(['POST']),
        ];

        yield 'Success already defined' => [
            'route' => '/foo',
            'method' => 'GET',
            'handler' => new RouteSuccess(static fn() => 'ahandler'),
            'request' => new ServerRequest('GET', '/foo'),
            'expectedResult' => new RouteSuccess(static fn() => 'ahandler'),
        ];
    }

    #[Test, DataProvider('routeExamples')]
    public function routeResults(
        string $route,
        \Closure|RouteDefinition|RouteSuccess $handler,
        ServerRequestInterface $request,
        RouteResult $expectedResult,
        string $method = 'GET',
    ): void
    {
        $routeCollector = new RouteCollector(new Std(), new GroupGenerator());

        $routeCollector->addRoute($method, $route, $handler);

        $dispatcher = new GroupDispatcher($routeCollector->getData());

        $router = new FastRouteRouter($dispatcher);

        $result = $router->route($request);

        self::assertEquals($expectedResult::class, $result::class);
        if ($expectedResult instanceof RouteSuccess) {
            self::assertInstanceOf(RouteSuccess::class, $result);
            self::assertEquals($expectedResult->arguments, $result->arguments);
            if ($handler instanceof RouteDefinition) {
                self::assertEquals($handler->actionDef, $result->actionDef);
            } else {
                self::assertEquals($expectedResult->actionDef, $result->actionDef);
            }
        }
    }
}

class FakeActions
{
    public function noParams(): string
    {
        return __FUNCTION__;
    }

    public function stringParam(string $name): string
    {
        return $name;
    }
}

class FakeInvokableAction
{
    public function __invoke(string $key): string
    {
        return $key;
    }
}
