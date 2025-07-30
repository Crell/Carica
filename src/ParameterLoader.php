<?php

declare(strict_types=1);

namespace Crell\Carica;

/**
 * Converter from an incoming primitive to a loaded object.
 *
 * Often this will be loading an Entity from a database based
 * on an ID provided as the value. That is not the only use case,
 * however, and any form of "upcasting" is allowed as long as
 * the resulting type is as expected.
 */
interface ParameterLoader
{
    /**
     * Loads the object that corresponds to the provided value.
     *
     * @param int|string|float $value
     *   The value to use to load an object.  Often an Entity ID of some kind.
     * @phpstan-param class-string $type
     *   The type to which the $value should be converted, if possible.
     * @return object|null
     *   A loaded object, or null to indicate this loader could not handle it
     *   and another loader can try.
     */
    public function load(int|string|float $value, string $type): ?object;
}
