<?php

declare(strict_types=1);

namespace Crell\Carica;

/**
 * It's possible this should merge with argument upcasters.
 */
interface BodyParser
{
    /**
     * Pseudo mime type for when the body is already parsed to an array, but may be converted further.
     */
    public const string PhpArrayType = 'application/vnd.carica.php-array';

    /**
     * @phpstan-param class-string $className
     */
    public function canParse(string $contentType, string $className): bool;

    /**
     * @phpstan-param class-string $className
     * @phpstan-param array<mixed, mixed> $unparsed
     * @return object
     *
     * @todo Add PHPStan generics here.
     */
    public function parse(string $contentType, string|array $unparsed, string $className): object;
}
