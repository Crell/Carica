<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\AttributeUtils\Analyzer;
use Crell\HttpTools\Fakes\ActionExamples;
use Crell\HttpTools\Fakes\ActionUnionTypeExample;
use Crell\HttpTools\Fakes\SecondMiddleware;
use Crell\HttpTools\Fakes\TracingMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ActionAttributeTest extends TestCase
{
    public static function attributesExamples(): \Generator
    {
        yield 'extra middleware' => [
            'class' => ActionExamples::class,
            'method' => 'oneExtraMiddleware',
            'tests' => function (ActionMetadataAttribute $def) {
                self::assertEquals([TracingMiddleware::class], $def->additionalMiddleware);
            },
        ];
        yield 'two extra middleware' => [
            'class' => ActionExamples::class,
            'method' => 'twoExtraMiddleware',
            'tests' => function (ActionMetadataAttribute $def) {
                self::assertEquals([TracingMiddleware::class, SecondMiddleware::class], $def->additionalMiddleware);
            },
        ];
        yield 'all parameter parts' => [
            'class' => ActionExamples::class,
            'method' => 'allParameterParts',
            'tests' => function (ActionMetadataAttribute $def) {
                self::assertEquals([
                    'point' => Point::class,
                    'request' => ServerRequestInterface::class,
                    'fromUrl' => 'string',
                    'beep' => 'string',
                ], $def->parameterTypes);
                self::assertEquals('point', $def->parsedBodyParameter);
                self::assertEquals('request', $def->requestParameter);
                self::assertEquals(['beep' => 'narf'], $def->requestAttributes);
            },
        ];
        yield 'unions invalid' => [
            'class' => ActionUnionTypeExample::class,
            'method' => 'hasUnions',
            'expectedException' => \InvalidArgumentException::class,

        ];
    }

    /**
     * @phpstan-param class-string $class
     * @phpstan-param class-string<\Throwable> $expectedException
     */
    #[Test]
    #[DataProvider('attributesExamples')]
    public function readAttributesFromClass(
        string $class,
        string $method,
        ?\Closure $tests = null,
        ?string $expectedException = null,
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $analyzer = new Analyzer();

        /** @var ActionClass $classDef */
        $classDef = $analyzer->analyze($class, ActionClass::class);

        $actionDef = $classDef->methods[$method];

        if ($tests) {
            $tests($actionDef);
        }
    }
}
