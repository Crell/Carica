<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Crell\HttpTools\ActionMetadata;
use Crell\HttpTools\ExplicitActionMetadata;
use Crell\HttpTools\Point;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ActionDispatcherTest extends TestCase
{
    public static function actionDispatcherExamples(): \Generator
    {
        yield 'basic' => [
            'url' => '/foo',
            'action' => fn() => new Response(200, body: 'success'),
            'arguments' => [],
            'meta' => new ExplicitActionMetadata([], null, null),
            'expectedResponseBody' => 'success',
            'expectedStatus' => 200,
        ];

        yield 'no renderer' => [
            'url' => '/foo',
            'action' => fn() => new Point(3, 5),
            'arguments' => [],
            'meta' => new ExplicitActionMetadata([], null, null),
            'expectedException' => ActionResultNotRendered::class,
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @phpstan-param class-string<\Throwable> $expectedException
     */
    #[Test, DataProvider('actionDispatcherExamples')]
    public function actionDispatcher(
        string $url,
        \Closure $action,
        array $arguments = [],
        ?ActionMetadata $meta = null,
        string $expectedResponseBody = '',
        int $expectedStatus = 200,
        ?string $expectedException = null,
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $dispatcher = new ActionDispatcher();

        $routeResult = new RouteSuccess($action, $arguments, $meta);

        $request = new ServerRequest('GET', $url)
            ->withAttribute(RouteResult::class, $routeResult);

        $response = $dispatcher->handle($request);

        self::assertEquals($expectedResponseBody, $response->getBody()->getContents());
        self::assertEquals($expectedStatus, $response->getStatusCode());
    }
}
