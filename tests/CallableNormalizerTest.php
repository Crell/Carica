<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CallableNormalizerTest extends TestCase
{

    public static function normalizerExamples(): \Generator
    {
        yield 'closure' => [
            'callable' => fn() => 'hello',
        ];
        yield 'class-string' => [
            'callable' => InvokableClass::class,
        ];
        yield 'invokable object' => [
            'callable' => new InvokableClass(),
        ];
        yield 'array' => [
            'callable' => [GeneralClass::class, 'say'],
        ];
        yield 'service' => [
            'callable' => ServiceClass::class,
            'expected' => 'service',
            'container' => new FakeContainer(),
        ];
    }

    #[Test, DataProvider('normalizerExamples')]
    public function normalizer(
        mixed $callable,
        string $expected = 'hello',
        ?ContainerInterface $container = null,
    ): void {
        $normalizer = new CallableNormalizer($container);

        $result = $normalizer->normalize($callable);

        self::assertEquals($expected, $result());
    }
}

class InvokableClass
{
    public function __invoke(): string
    {
        return 'hello';
    }
}

class GeneralClass
{
    public function say(): string
    {
        return 'hello';
    }
}

class ServiceClass
{
    public function __invoke(): string
    {
        return 'service';
    }
}

class FakeContainer implements ContainerInterface
{
    public function get(string $id): ServiceClass
    {
        return new ServiceClass();
    }

    public function has(string $id): bool
    {
        return true;
    }
}
