<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\AttributeUtils\Multivalue;

#[\Attribute(\Attribute::TARGET_FUNCTION|\Attribute::TARGET_METHOD|\Attribute::IS_REPEATABLE)]
readonly class Middleware implements Multivalue
{
    /**
     * @param string|class-string $name
     *   The service ID or class name (usually the same thing in practice) of
     *   this middleware.
     */
    public function __construct(
        public string $name,
    ) {}
}
