<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

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
            'parameters' => [],
            'expectedResponseBody' => 'success',
            'expectedStatus' => 200,
        ];
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, string> $parameters
     */
    #[Test, DataProvider('actionDispatcherExamples')]
    public function actionDispatcher(
        string $url,
        \Closure $action,
        array $arguments = [],
        array $parameters = [],
        string $expectedResponseBody = '',
        int $expectedStatus = 200,
    ): void {
        $dispatcher = new ActionDispatcher();

        $routeResult = new RouteSuccess($action, $arguments, $parameters);

        $request = new ServerRequest('GET', $url)
            ->withAttribute(RouteResult::class, $routeResult);

        $response = $dispatcher->handle($request);

        self::assertEquals($expectedResponseBody, $response->getBody()->getContents());
        self::assertEquals($expectedStatus, $response->getStatusCode());
    }
}
