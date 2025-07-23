<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Crell\HttpTools\Point;
use Crell\HttpTools\ResponseBuilder;
use Crell\Serde\SerdeCommon;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JsonResultRendererTest extends TestCase
{
    public static function renderExamples(): \Generator
    {
        yield 'int' => [
            'value' => 1,
            'expectedBody' => '1',
        ];
        yield 'string' => [
            'value' => 'hello',
            'expectedBody' => 'hello',
        ];
        yield 'float' => [
            'value' => 3.14,
            'expectedBody' => '3.14',
        ];
        yield 'bool' => [
            'value' => true,
            'expectedBody' => '1',
        ];
        yield 'array' => [
            'value' => ['hello' => 'world'],
            'expectedBody' => json_encode(['hello' => 'world'], JSON_THROW_ON_ERROR),
        ];
        yield 'object, no serde' => [
            'value' => new Point(3, 4),
            'expectedBody' => json_encode(['x' => 3, 'y' => 4], JSON_THROW_ON_ERROR),
        ];
        yield 'object, with serde' => [
            'value' => new Point(3, 4),
            'serde' => true,
            'expectedBody' => json_encode(['x' => 3, 'y' => 4], JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @param mixed $value
     * @param bool $serde
     * @param class-string<\Throwable>|null $expectedException
     */
    #[Test]
    #[DataProvider('renderExamples')]
    public function render(
        mixed $value,
        mixed $expectedBody,
        bool $serde = false,
        ?string $expectedException = null,
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($factory, $factory);

        $render = new JsonResultRenderer($responseBuilder, $serde ? new SerdeCommon() : null);

        $response = $render->renderResponse(new ServerRequest('GET', '/foo'), $value);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaderLine('content-type'));

        $body = $response->getBody()->getContents();
        self::assertEquals($expectedBody, $body);
    }
}
