<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\HttpTools\Router\FakeNext;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StackMiddlewareKernelTest extends TestCase
{
    #[Test, TestDox('All middleware are optional')]
    public function noMiddleware(): void
    {
        $fakeNext = new FakeNext();

        $stack = new StackMiddlewareKernel($fakeNext);

        $request = new ServerRequest('GET', '/foo/bar');

        $response = $stack->handle($request);

        // FakeNext is expecting a route result, so just verify it does its default behavior.
        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals('no route info', $response->getBody()->getContents());
    }

    #[Test, TestDox('Middleware added with addMiddleware are "inside out."')]
    public function addMiddlewareOrder(): void
    {
        $fakeNext = new FakeNext();

        $stack = new StackMiddlewareKernel($fakeNext);

        $stack
            ->addMiddleware($this->stackTrackingMiddleware('A'))
            ->addMiddleware($this->stackTrackingMiddleware('B'))
            ->addMiddleware($this->stackTrackingMiddleware('C'))
        ;

        $request = new ServerRequest('GET', '/foo/bar');

        $response = $stack->handle($request);

        $callStack = $fakeNext->request?->getAttribute('callStack');

        self::assertEquals(['C', 'B', 'A'], $callStack);
    }

    #[Test, TestDox('Middleware added by the constructor are "outside in."')]
    public function constructorOrder(): void
    {
        $fakeNext = new FakeNext();

        $stack = new StackMiddlewareKernel($fakeNext, [
            $this->stackTrackingMiddleware('A'),
            $this->stackTrackingMiddleware('B'),
            $this->stackTrackingMiddleware('C'),
        ]);

        $request = new ServerRequest('GET', '/foo/bar');

        $response = $stack->handle($request);

        $callStack = $fakeNext->request?->getAttribute('callStack');

        self::assertEquals(['A', 'B', 'C'], $callStack);
    }

    private function stackTrackingMiddleware(string $name): MiddlewareInterface
    {
        return new class($name) implements MiddlewareInterface
        {
            public function __construct(public string $name) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $callStack = $request->getAttribute('callStack') ?? [];
                $callStack[] = $this->name;
                $request = $request->withAttribute('callStack', $callStack);

                return $handler->handle($request);
            }
        };
    }
}
