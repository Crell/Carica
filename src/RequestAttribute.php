<?php

declare(strict_types=1);

namespace Crell\HttpTools;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class RequestAttribute extends ActionParameter
{
    /**
     * @param string|null $name
     *   The name of the request attribute to pass here.
     *   If not specified, the parameter name will be used.
     */
    public function __construct(
        protected(set) ?string $name = null,
    ) {}
}
