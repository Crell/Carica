<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\ResponseBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\Test\TestLogger;

class ExceptionCatcherMiddlewareTest extends TestCase
{
    public static function exceptionExamples(): \Generator
    {
        yield 'debug on' => [
            'debug' => true,
            'expectedBody' => "exception thrown: " . __FILE__ . '%s',
        ];
        yield 'debug off' => [
            'debug' => false,
            'expectedBody' => '',
        ];
    }

    #[Test, DataProvider('exceptionExamples')]
    public function exceptionMiddleware(
        bool $debug,
        string $expectedBody,
    ): void
    {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        $logger = new TestLogger();

        $middleware = new ExceptionCatcherMiddleware($responseBuilder, $logger, $debug);

        $request = new ServerRequest('GET', '/');

        $throwNext = new class implements RequestHandlerInterface {
            private(set) ?ServerRequestInterface $request = null;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                throw new \RuntimeException('exception thrown');
            }
        };

        $response = $middleware->process($request, $throwNext);

        self::assertNotNull($throwNext->request);
        self::assertEquals(500, $response->getStatusCode());
        self::assertStringMatchesFormat($expectedBody, $response->getBody()->getContents());
    }
}
