<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\FakeNext;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DefaultContentTypeMiddlewareTest extends TestCase
{
    public static function defaultCases(): \Generator
    {
        yield 'no defaults' => [
            'contentType' => null,
            'accept' => null,
        ];
        yield 'just content type' => [
            'contentType' => 'application/json',
            'accept' => null,
        ];
        yield 'just accept' => [
            'contentType' => null,
            'accept' => 'application/json',
        ];
        yield 'both headers' => [
            'contentType' => 'application/json',
            'accept' => 'application/json',
        ];
    }

    #[Test, DataProvider('defaultCases')]
    public function defaults(
        ?string $contentType,
        ?string $accept,
    ): void
    {
        $middleware = new DefaultContentTypeMiddleware($contentType, $accept);

        $fakeNext = new FakeNext();
        $response = $middleware->process(new ServerRequest('GET', '/foo'), $fakeNext);

        self::assertEquals($contentType, $fakeNext->request?->getHeaderLine('content-type'));
        self::assertEquals($accept, $fakeNext->request?->getHeaderLine('accept'));
    }
}
