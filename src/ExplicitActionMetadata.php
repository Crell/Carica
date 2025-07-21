<?php

declare(strict_types=1);

namespace Crell\HttpTools;

class ExplicitActionMetadata implements ActionMetadata
{
    public function __construct(
        public private(set) ?array $parameterTypes = [],
        public private(set) ?string $parsedBodyParameter = null,
        public private(set) ?string $requestParameter = null,
    ) {}
}
