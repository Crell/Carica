<?php

declare(strict_types=1);

namespace Crell\Carica;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseBuilderTest extends TestCase
{
    private function builder(): ResponseBuilder
    {
        $factory = new Psr17Factory();
        return new ResponseBuilder($factory, $factory);
    }

    #[Test]
    public function arbitrary_no_type(): void
    {
        $response = $this->builder()->createResponse(HttpStatus::OK, 'Hello world');

        self::assertEquals('Hello world', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('', $response->getHeaderLine('content-type'));
    }

    #[Test]
    public function arbitrary_with_type(): void
    {
        $response = $this->builder()->createResponse(HttpStatus::OK, '{"hello": "world"}', 'application/json');

        self::assertEquals('{"hello": "world"}', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaderLine('content-type'));
    }

    #[Test]
    public function arbitrary_with_stream_body(): void
    {
        $stream = (new Psr17Factory())->createStream('Hello World');
        $response = $this->builder()->createResponse(HttpStatus::OK, $stream);

        self::assertEquals('Hello World', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function ok(): void
    {
        $response = $this->builder()->ok('{"hello": "world"}', 'application/json');

        self::assertEquals('{"hello": "world"}', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaderLine('content-type'));
    }

    #[Test]
    public function created(): void
    {
        $response = $this->builder()->created('http://example.com');

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(201, $response->getStatusCode());
        self::assertEquals('http://example.com', $response->getHeaderLine('location'));
    }

    #[Test]
    public function noContent(): void
    {
        $response = $this->builder()->noContent();

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(204, $response->getStatusCode());
    }

    #[Test]
    public function seeOther(): void
    {
        $response = $this->builder()->seeOther('http://example.com');

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(303, $response->getStatusCode());
        self::assertEquals('http://example.com', $response->getHeaderLine('location'));
    }

    #[Test]
    public function notModified(): void
    {
        $response = $this->builder()->notModified();

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(304, $response->getStatusCode());
    }

    #[Test]
    public function temporaryRedirect(): void
    {
        $response = $this->builder()->temporaryRedirect('http://example.com');

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(307, $response->getStatusCode());
        self::assertEquals('http://example.com', $response->getHeaderLine('location'));
    }

    #[Test]
    public function permanentRedirect(): void
    {
        $response = $this->builder()->permanentRedirect('http://example.com');

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(308, $response->getStatusCode());
        self::assertEquals('http://example.com', $response->getHeaderLine('location'));
    }

    #[Test]
    public function badRequest(): void
    {
        $response = $this->builder()->badRequest('Bad Request');

        self::assertEquals('Bad Request', $response->getBody()->getContents());
        self::assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function unauthorized(): void
    {
        $response = $this->builder()->unauthorized('Unauthorized');

        self::assertEquals('Unauthorized', $response->getBody()->getContents());
        self::assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function notFound(): void
    {
        $response = $this->builder()->notFound('Not Found');

        self::assertEquals('Not Found', $response->getBody()->getContents());
        self::assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function forbidden(): void
    {
        $response = $this->builder()->forbidden('Go away');

        self::assertEquals('Go away', $response->getBody()->getContents());
        self::assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function gone(): void
    {
        $response = $this->builder()->gone('Gone away');

        self::assertEquals('Gone away', $response->getBody()->getContents());
        self::assertEquals(410, $response->getStatusCode());
    }

    #[Test]
    public function methodNotAllowed(): void
    {
        $response = $this->builder()->methodNotAllowed(['get', 'post']);

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(405, $response->getStatusCode());
        self::assertEquals('GET, POST', $response->getHeaderLine('allow'));
    }

    #[Test]
    public function unsupportedMedia(): void
    {
        $response = $this->builder()->unsupportedMediaType(['application/json', 'text/plain']);

        self::assertEquals('', $response->getBody()->getContents());
        self::assertEquals(415, $response->getStatusCode());
        self::assertEquals('application/json, text/plain', $response->getHeaderLine('accept'));
    }
}
