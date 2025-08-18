<?php

declare(strict_types=1);

namespace Crell\Carica\Router\FastRoute;

use Crell\Carica\Fakes\ActionExamples;
use Crell\Carica\Fakes\InvokableAction;
use Crell\Carica\Router\RouteDefinition;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PreParsingRouteCollectorTest extends TestCase
{
    public static function routeExamples(): \Generator
    {
        yield 'static route' => [
            'routeCallback' => static function(PreParsingRouteCollector $r) {
                $r->get('/foo/bar', [ActionExamples::class, 'simple']);
            },
            'tests' => static function(RouteDispatcher $dispatcher) {
                $result = $dispatcher->dispatch('GET', '/foo/bar');

                self::assertEquals(Dispatcher::FOUND, $result[0]);
                self::assertInstanceOf(RouteDefinition::class, $result[1]);
                self::assertEquals([ActionExamples::class, 'simple'], $result[1]->action);
                self::assertEquals([], $result[2]);
            },
        ];
        yield 'invokable action' => [
            'routeCallback' => static function(PreParsingRouteCollector $r) {
                $r->get('/foo/bar', InvokableAction::class);
            },
            'tests' => static function(RouteDispatcher $dispatcher) {
                $result = $dispatcher->dispatch('GET', '/foo/bar');

                self::assertEquals(Dispatcher::FOUND, $result[0]);
                self::assertInstanceOf(RouteDefinition::class, $result[1]);
                self::assertEquals([InvokableAction::class, '__invoke'], $result[1]->action);
                self::assertEquals([], $result[2]);
            },
        ];
        yield 'placeholder route' => [
            'routeCallback' => static function(PreParsingRouteCollector $r) {
                $r->get('/foo/{bar}/baz', [ActionExamples::class, 'simple']);
            },
            'tests' => static function(RouteDispatcher $dispatcher) {
                $result = $dispatcher->dispatch('GET', '/foo/beep/baz');

                self::assertEquals(Dispatcher::FOUND, $result[0]);
                self::assertInstanceOf(RouteDefinition::class, $result[1]);
                self::assertEquals(['bar' => 'beep'], $result[2]);
            },
        ];
        yield 'placeholder route with extra args' => [
            'routeCallback' => static function(PreParsingRouteCollector $r) {
                $r->get('/foo/{bar}/baz', [ActionExamples::class, 'simple'], ['extra'=> 'args']);
            },
            'tests' => static function(RouteDispatcher $dispatcher) {
                $result = $dispatcher->dispatch('GET', '/foo/beep/baz');

                self::assertEquals(Dispatcher::FOUND, $result[0]);
                self::assertInstanceOf(RouteDefinition::class, $result[1]);
                self::assertEquals(['extra' => 'args'], $result[1]->extraArguments);
                self::assertEquals(['bar' => 'beep'], $result[2]);
            },
        ];
        yield 'grouped route' => [
            'routeCallback' => static function(PreParsingRouteCollector $r) {
                $r->addGroup('/foo', function (PreParsingRouteCollector $r) {
                    $r->get('/{bar}/baz', [ActionExamples::class, 'simple']);
                });
            },
            'tests' => static function(RouteDispatcher $dispatcher) {
                $result = $dispatcher->dispatch('GET', '/foo/beep/baz');

                self::assertEquals(Dispatcher::FOUND, $result[0]);
                self::assertInstanceOf(RouteDefinition::class, $result[1]);
                self::assertEquals(['bar' => 'beep'], $result[2]);
            },
        ];

        yield 'route not found' => [
            'routeCallback' => static function(PreParsingRouteCollector $r) {
                $r->get('/foo/bar', [ActionExamples::class, 'simple']);
            },
            'tests' => static function(RouteDispatcher $dispatcher) {
                $result = $dispatcher->dispatch('GET', '/beep');

                self::assertEquals(Dispatcher::NOT_FOUND, $result[0]);
            },
        ];
    }

    #[Test, DataProvider('routeExamples')]
    public function routeCollector(\Closure $routeCallback, \Closure $tests): void
    {
        $collector = new PreParsingRouteCollector();

        $routeCallback($collector);

        $dispatcher = new RouteDispatcher($collector->getData());

        $tests($dispatcher);
    }
}
