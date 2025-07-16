<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\HttpTools\Middleware\CacheHeaderMiddleware;
use Crell\HttpTools\Middleware\DefaultContentTypeMiddleware;
use Crell\HttpTools\Middleware\DeriveActionParametersMiddleware;
use Crell\HttpTools\Middleware\EnforceHeadMiddleware;
use Crell\HttpTools\Middleware\GenericMethodNotAllowedMiddleware;
use Crell\HttpTools\Middleware\GenericNotFoundMiddleware;
use Crell\HttpTools\Middleware\NormalizeScalarArgumentsMiddleware;
use Crell\HttpTools\Middleware\QueryParametersMiddleware;
use Crell\HttpTools\Router\ActionDispatcher;
use Crell\HttpTools\Router\JsonResultRenderer;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouterMiddleware;
use Crell\HttpTools\Router\RouteSuccess;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IntegrationTest extends TestCase
{
    public static function standardConfigurationExamples(): \Generator
    {
        yield 'Successful array response' => [
            'request' => new ServerRequest('GET', '/foo/bar'),
            'routeResult' => new RouteSuccess(fn() => ['hello' => 'world']),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                $json = json_decode($body, true);
                self::assertEquals(['hello' => 'world'], $json);
            },
        ];

        yield 'String query passed' => [
            'request' => new ServerRequest('GET', '/foo/bar?baz=world'),
            'routeResult' => new RouteSuccess(fn(string $baz) => ['hello' => $baz]),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                $json = json_decode($body, true);
                self::assertEquals(['hello' => 'world'], $json);
            },
        ];

        yield 'Int query passed' => [
            'request' => new ServerRequest('GET', '/foo/bar?baz=1'),
            'routeResult' => new RouteSuccess(fn(int $baz) => ['hello' => $baz]),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                $json = json_decode($body, true);
                self::assertEquals(['hello' => 1], $json);
            },
        ];

        yield 'String placeholder passed' => [
            'request' => new ServerRequest('GET', '/foo/beep'),
            'routeResult' => new RouteSuccess(action: fn(string $baz) => ['baz' => $baz], arguments: ['baz' => 'beep']),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                $json = json_decode($body, true);
                self::assertSame(['baz' => 'beep'], $json);
            },
        ];

        yield 'Int placeholder passed' => [
            'request' => new ServerRequest('GET', '/foo/1'),
            'routeResult' => new RouteSuccess(action: fn(int $baz) => ['baz' => $baz], arguments: ['baz' => '1']),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                $json = json_decode($body, true);
                self::assertSame(['baz' => 1], $json);
            },
        ];

        yield 'String placeholder passed an int' => [
            'request' => new ServerRequest('GET', '/foo/1'),
            'routeResult' => new RouteSuccess(action: fn(string $baz) => ['baz' => $baz], arguments: ['baz' => '1']),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                $json = json_decode($body, true);
                self::assertSame(['baz' => '1'], $json);
            },
        ];
    }

    #[Test, TestDox('A standard middleware configuration works as expected')]
    #[DataProvider('standardConfigurationExamples')]
    public function standardConfiguration(
        ServerRequestInterface $request,
        RouteResult $routeResult,
        int $expectedStatus = 200,
        ?\Closure $tests = null,
    ): void {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        $dispatcher = new ActionDispatcher(new JsonResultRenderer($responseBuilder));

        $stack = new StackMiddlewareKernel($dispatcher, [
            new DefaultContentTypeMiddleware('application/json'),
            new CacheHeaderMiddleware(),
            new EnforceHeadMiddleware($psr17Factory),
            new RouterMiddleware(new MockRouter($routeResult)),
            new GenericNotFoundMiddleware($responseBuilder),
            new GenericMethodNotAllowedMiddleware($responseBuilder),
            new DeriveActionParametersMiddleware(),
            new QueryParametersMiddleware(),
            new NormalizeScalarArgumentsMiddleware(),
        ]);

        $response = $stack->handle($request);

        self::assertEquals($expectedStatus, $response->getStatusCode());

        if ($tests) {
            $tests($response, $response->getBody()->getContents());
        }
    }
}
