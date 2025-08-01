<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\FakeNext;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\Test\TestLogger;

class LogMiddlewareTest extends TestCase
{
    #[Test, TestDox('A request is logged with no other changes')]
    public function logHappens(): void
    {
        $request = new ServerRequest('GET', '/foo');
        $next = new FakeNext();
        $logger = new TestLogger();

        $middleware = new LogMiddleware($logger);

        $response = $middleware->process($request, $next);

        self::assertTrue($logger->hasInfo([
            'level' => 'info',
            'message' => 'Request received to {path}',
            'context' => ['path' => '/foo'],
        ]));

        self::assertInstanceOf(ServerRequestInterface::class, $next->request);
    }
}
