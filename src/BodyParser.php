<?php

declare(strict_types=1);

namespace Crell\HttpTools;

/**
 * It's possible this should merge with argument upcasters.
 */
interface BodyParser
{
    /**
     * @phpstan-param class-string $className
     */
    public function canParse(string $contentType, string $className): bool;

    /**
     * @phpstan-param class-string $className
     * @return object
     *
     * @todo Add PHPStan generics here.
     */
    public function parse(string $contentType, string $unparsed, string $className): object;
}
