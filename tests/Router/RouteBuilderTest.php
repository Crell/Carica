<?php

declare(strict_types=1);


namespace Crell\Carica\Router;

use Crell\Carica\ActionMetadata;
use Crell\Carica\ExplicitActionMetadata;
use Crell\Carica\Fakes\ActionExamples;
use Crell\Carica\Fakes\InvokableAction;
use Crell\Carica\HttpMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteBuilderTest extends TestCase
{
    public static function routeExamples(): \Generator
    {
        yield 'multiple methods' => [
            'httpMethod' => ['get', HttpMethod::Post],
            'route' => '/foo/bar',
            'action' => [ActionExamples::class, 'simple'],
            'expectedMethods' => ['GET', 'POST'],
            'expectedDef' => new RouteDefinition([ActionExamples::class, 'simple'], new ExplicitActionMetadata(['a' => 'string'])),
        ];

        yield 'invokable class' => [
            'httpMethod' => 'get',
            'route' => '/foo/bar',
            'action' => InvokableAction::class,
            'expectedMethods' => ['GET'],
            'expectedDef' => new RouteDefinition([InvokableAction::class, '__invoke'], new ExplicitActionMetadata(['a' => 'string'])),
        ];

        // Listing this last so it doesn't break the IDE's run icons for individual test cases.
        foreach (['get', 'GET', ['GET'], ['geT'], HttpMethod::Get, [HttpMethod::Get]] as $method) {
            // Making a string out of each of the above for the test label is just too much work...
            yield [
                'httpMethod' => 'GET',
                'route' => '/foo/bar',
                'action' => [ActionExamples::class, 'simple'],
                'expectedMethods' => ['GET'],
                'expectedDef' => new RouteDefinition([ActionExamples::class, 'simple'], new ExplicitActionMetadata(['a' => 'string'])),
            ];
        }
    }

    /**
     * @param string|HttpMethod|array<string|HttpMethod> $httpMethod
     * @param class-string|array{class-string, string} $action
     * @param array<string, scalar> $extraArguments
     * @param string[] $expectedMethods
     */
    #[Test]
    #[DataProvider('routeExamples')]
    public function routeBuilder(
        string|HttpMethod|array $httpMethod,
        string $route,
        string|array $action,
        array $expectedMethods,
        RouteDefinition $expectedDef,
        array $extraArguments = [],
    ): void
    {
        $driver = new FakeRouteBuilderDriver();

        $builder = new RouteBuilder($driver);

        $builder->route($httpMethod, $route, $action, $extraArguments);

        $added = $driver->added[0] ?? null;

        self::assertNotNull($added);
        self::assertEquals($expectedMethods, $added['method']);
        self::assertEquals($route, $added['route']); // @phpstan-ignore offsetAccess.notFound (I think PHPStan is wrong on this one.)
        self::assertEquals($expectedDef->action, $added['routeDef']->action); // @phpstan-ignore offsetAccess.notFound (I think PHPStan is wrong on this one.)
        self::assertEqualActionMetadata($expectedDef->actionDef, $added['routeDef']->actionDef); // @phpstan-ignore offsetAccess.notFound (I think PHPStan is wrong on this one.)
    }

    protected static function assertEqualActionMetadata(ActionMetadata $expected, ActionMetadata $actual): void
    {
        $rClass = new \ReflectionClass(ActionMetadata::class);
        $rProps = $rClass->getProperties();
        $properties = array_map(static fn(\ReflectionProperty $p) => $p->getName(), $rProps);

        // ['parameterTypes', 'parsedBodyParameter', 'requestParameter', 'requestAttributes', 'uploadedFileParameters', '']

        foreach ($properties as $prop) {
            self::assertSame($expected->$prop, $actual->$prop);
        }
    }
}
