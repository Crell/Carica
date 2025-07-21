<?php

declare(strict_types=1);

namespace Crell\HttpTools;

readonly class ExplicitActionMetadata implements ActionMetadata
{
    /**
     * @param array<string, string> $parameterTypes
     */
    public function __construct(
        public private(set) array $parameterTypes = [],
        public private(set) ?string $parsedBodyParameter = null,
        public private(set) ?string $requestParameter = null,
    ) {}
}
