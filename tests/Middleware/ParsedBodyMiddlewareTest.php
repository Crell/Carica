<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\ParsedBody;
use Crell\HttpTools\Point;
use Crell\HttpTools\ResponseBuilder;
use Crell\HttpTools\Router\FakeNext;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteSuccess;
use Crell\HttpTools\SerdeBodyParser;
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
                parameters: [],
                parsedBodyParameter: '',
            ),
            'expectedParsedBody' => null,
        ];

        yield 'JSON body, body param' => [
            'body' => '{"x": 3, "y": 5}',
            'routeResult' => new RouteSuccess(
                action: fn(#[ParsedBody] Point $body) => $body,
                parameters: ['body' => Point::class],
                parsedBodyParameter: 'body',
            ),
            'expectedParsedBody' => new Point(3, 5),
        ];
    }

    #[Test, DataProvider('bodyParsingExamples')]
    public function bodyParsing(string $body, RouteResult $routeResult, mixed $expectedParsedBody): void
    {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        $middleware = new ParsedBodyMiddleware($responseBuilder, [new SerdeBodyParser(new SerdeCommon())]);

        $request = new ServerRequest(
            method: 'GET',
            uri: '/foo/bar',
            headers: ['content-type' => 'application/json'],
            body: $body)
            ->withAttribute(RouteResult::class, $routeResult)
        ;

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        self::assertNotNull($fakeNext->request);
        /** @var RouteSuccess $updatedResult */
        $updatedResult = $fakeNext->request->getAttribute(RouteResult::class);

        self::assertEquals($expectedParsedBody, $fakeNext->request->getParsedBody());
    }
}
