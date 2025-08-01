<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\ExplicitActionMetadata;
use Crell\Carica\FakeNext;
use Crell\Carica\HasX;
use Crell\Carica\ParameterLoader;
use Crell\Carica\Point;
use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class NormalizeArgumentTypesMiddlewareTest extends TestCase
{
    public static function scalarMappingExamples(): \Generator
    {
        yield 'int to int' => [
            'type' => 'int',
            'value' => 5,
            'expectedValue' => 5,
        ];
        yield 'float to float' => [
            'type' => 'float',
            'value' => 3.14,
            'expectedValue' => 3.14,
        ];
        yield 'string to string' => [
            'type' => 'string',
            'value' => 'hello',
            'expectedValue' => 'hello',
        ];
        yield 'numeric string to string' => [
            'type' => 'string',
            'value' => '5',
            'expectedValue' => '5',
        ];
        yield 'int to float' => [
            'type' => 'float',
            'value' => 5,
            'expectedValue' => 5.0,
        ];
        yield 'numeric string to int' => [
            'type' => 'int',
            'value' => '3',
            'expectedValue' => 3,
        ];
        yield 'numeric string to float' => [
            'type' => 'float',
            'value' => '3.14',
            'expectedValue' => 3.14,
        ];

        // Booleans
        foreach ([1, '1', 'true', 'yes', 'on'] as $val) {
            yield sprintf('%s "%s" to bool', get_debug_type($val), $val) => [
                'type' => 'bool',
                'value' => $val,
                'expectedValue' => true,
            ];
        }
        foreach ([0, '0', 'false', 'no', 'off'] as $val) {
            yield sprintf('%s "%s" to bool', get_debug_type($val), $val) => [
                'type' => 'bool',
                'value' => $val,
                'expectedValue' => false,
            ];
        }
        foreach ([2, '2', 'nyet', 'agreed'] as $val) {
            yield sprintf('%s "%s" to bool', get_debug_type($val), $val) => [
                'type' => 'bool',
                'value' => $val,
                'expectedResponseCode' => 400,
            ];
        }

    }

    public static function objectMappingExamples(): \Generator
    {
        yield 'string to object (ignored)' => [
            'type' => 'AClassName',
            'value' => 'hello',
            'expectedValue' => 'hello',
        ];
        yield 'int to object (ignored)' => [
            'type' => 'AClassName',
            'value' => 5,
            'expectedValue' => 5,
        ];

        $pointLoader = new class implements ParameterLoader
        {
            public function load(float|int|string $value, string $type): ?object
            {
                if (is_int($value)) {
                    return new Point($value, 0);
                }
                return null;
            }
        };
        $nonLoader = new class implements ParameterLoader
        {
            public function load(float|int|string $value, string $type): ?object
            {
                return null;
            }
        };

        yield 'int to object (loaded)' => [
            'type' => Point::class,
            'value' => 5,
            'loaders' => [Point::class => $pointLoader],
            'expectedValue' => new Point(5, 0),
        ];
        yield 'int to interface (loaded)' => [
            'type' => HasX::class,
            'value' => 5,
            'loaders' => [Point::class => $pointLoader],
            'expectedValue' => new Point(5, 0),
        ];
        yield 'int to interface with no-op loader' => [
            'type' => HasX::class,
            'value' => 5,
            'loaders' => [HasX::class => $nonLoader, Point::class => $pointLoader],
            'expectedValue' => new Point(5, 0),
        ];

        yield 'int to interface with no applicable loader' => [
            'type' => HasX::class,
            'value' => 5,
            'loaders' => [HasX::class => $nonLoader],
            'expectedResponseCode' => 400,
        ];
    }

    /**
     * @param array<class-string, ParameterLoader> $loaders
     */
    #[Test, TestDox('We can normalize from $_dataName')]
    #[DataProvider('scalarMappingExamples')]
    #[DataProvider('objectMappingExamples')]
    public function typeMapping(
        string $type,
        mixed $value,
        array $loaders = [],
        mixed $expectedValue = null,
        mixed $expectedResponseCode = null,
    ): void
    {
        $psr17Factory = new Psr17Factory();
        $responseBuilder = new ResponseBuilder($psr17Factory, $psr17Factory);

        $middleware = new NormalizeArgumentTypesMiddleware($responseBuilder, $loaders);

        $result = new RouteSuccess(
            action: fn(string $a) => $a,
            arguments: ['a' => $value],
            actionDef: new ExplicitActionMetadata(['a' => $type]),
        );
        $request = new ServerRequest('GET', '/foo/bar')
            ->withAttribute(RouteResult::class, $result)
        ;

        $fakeNext = new FakeNext();
        $response = $middleware->process($request, $fakeNext);

        if ($expectedValue !== null) {
            self::assertNotNull($fakeNext->request);
            /** @var RouteSuccess $updatedResult */
            $updatedResult = $fakeNext->request->getAttribute(RouteResult::class);

            self::assertEquals(['a' => $expectedValue], $updatedResult->arguments);
        }

        if ($expectedResponseCode) {
            self::assertEquals($expectedResponseCode, $response->getStatusCode());
        }
    }
}
