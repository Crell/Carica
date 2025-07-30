<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\ExplicitActionMetadata;
use Crell\Carica\ParsedBody;
use Crell\Carica\Point;
use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\FakeNext;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Crell\Carica\SerdeBodyParser;
use Crell\Serde\SerdeCommon;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParsedBodyMiddlewareTest extends TestCase
{
    public static function bodyParsingExamples(): \Generator
    {
        yield 'JSON body, no param' => [
            'body' => '{"x": 3, "y": 5}',
            'routeResult' => new RouteSuccess(
                action: fn() => 'action',
                actionDef: new ExplicitActionMetadata([], ''),
            ),
            'expectedParsedBody' => null,
        ];

        yield 'JSON body, body param' => [
            'body' => '{"x": 3, "y": 5}',
            'routeResult' => new RouteSuccess(
                action: fn(#[ParsedBody] Point $body) => $body,
                actionDef: new ExplicitActionMetadata(['body' => Point::class], 'body'),
            ),
            'expectedParsedBody' => new Point(3, 5),
        ];

        yield 'POST body, object body param' => [
            'body' => ['x' => 3, 'y' => 5],
            'routeResult' => new RouteSuccess(
                action: fn(#[ParsedBody] Point $body) => $body,
                actionDef: new ExplicitActionMetadata(['body' => Point::class], 'body'),
            ),
            'expectedParsedBody' => new Point(3, 5),
        ];

        yield 'POST body, array body param' => [
            'body' => ['x' => 3, 'y' => 5],
            'routeResult' => new RouteSuccess(
                action: fn(#[ParsedBody] array $body) => $body,
                actionDef: new ExplicitActionMetadata(['body' => 'array'], 'body'),
            ),
            'expectedParsedBody' => ['x' => 3, 'y' => 5],
        ];
    }

    /**
     * @param string|array<string, mixed> $body
     */
    #[Test, DataProvider('bodyParsingExamples')]
    public function bodyParsing(string|array $body, RouteResult $routeResult, mixed $expectedParsedBody): void
    {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        $middleware = new ParsedBodyMiddleware($responseBuilder, [new SerdeBodyParser(new SerdeCommon())]);

        if (is_string($body)) {
            $request = new ServerRequest(
                method: 'GET',
                uri: '/foo/bar',
                headers: ['content-type' => 'application/json'],
                body: $body)
            ;
        } else {
            $request = new ServerRequest(
                method: 'GET',
                uri: '/foo/bar',
                headers: ['content-type' => 'application/json'])
            ->withParsedBody($body)
            ;
        }

        $request = $request->withAttribute(RouteResult::class, $routeResult);

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        self::assertNotNull($fakeNext->request);
        /** @var RouteSuccess $updatedResult */
        $updatedResult = $fakeNext->request->getAttribute(RouteResult::class);

        self::assertEquals($expectedParsedBody, $fakeNext->request->getParsedBody());
    }
}
