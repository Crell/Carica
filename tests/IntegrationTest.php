<?php

declare(strict_types=1);

namespace Crell\Carica;

use Crell\Carica\Middleware\AdditionalMiddlewareMiddleware;
use Crell\Carica\Middleware\CacheHeaderMiddleware;
use Crell\Carica\Middleware\DefaultContentTypeMiddleware;
use Crell\Carica\Middleware\DeriveActionMetadataMiddleware;
use Crell\Carica\Middleware\EnforceHeadMiddleware;
use Crell\Carica\Middleware\GenericMethodNotAllowedMiddleware;
use Crell\Carica\Middleware\GenericNotFoundMiddleware;
use Crell\Carica\Middleware\NormalizeArgumentTypesMiddleware;
use Crell\Carica\Middleware\ParsedBodyMiddleware;
use Crell\Carica\Middleware\QueryParametersMiddleware;
use Crell\Carica\Router\ActionDispatcher;
use Crell\Carica\Router\JsonResultRenderer;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouterMiddleware;
use Crell\Carica\Router\RouteSuccess;
use Crell\Serde\SerdeCommon;
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

        yield 'Successful array response, HEAD' => [
            'request' => new ServerRequest('HEAD', '/foo/bar'),
            'routeResult' => new RouteSuccess(fn() => ['hello' => 'world']),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                self::assertEmpty($body);
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

        yield 'JSON body' => [
            'request' => new ServerRequest('POST', '/foo/bar', body: '{"x": 3, "y": 5}'),
            'routeResult' => new RouteSuccess(action: fn(#[ParsedBody] Point $body) => ['body' => $body, 'x' => $body->x]),
            'expectedStatus' => 200,
            'tests' => function (ResponseInterface $response, string $body) {
                $json = json_decode($body, true);
                self::assertSame(['body' => ['x' => 3, 'y' => 5], 'x' => 3], $json);
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
            new DeriveActionMetadataMiddleware(),
            new QueryParametersMiddleware(),
            new NormalizeArgumentTypesMiddleware($responseBuilder),
            new ParsedBodyMiddleware($responseBuilder, [
                new SerdeBodyParser(new SerdeCommon()),
            ]),
            new AdditionalMiddlewareMiddleware(),
        ]);

        $response = $stack->handle($request);

        self::assertEquals($expectedStatus, $response->getStatusCode());

        if ($tests) {
            $tests($response, $response->getBody()->getContents());
        }
    }
}
