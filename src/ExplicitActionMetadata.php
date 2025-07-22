<?php

declare(strict_types=1);

namespace Crell\HttpTools;

readonly class ExplicitActionMetadata implements ActionMetadata
{
    /**
     * @param array<string, string> $parameterTypes
     * @param array<string, string> $requestAttributes
     * @param array<string|class-string> $additionalMiddleware
     */
    public function __construct(
        private(set) array $parameterTypes = [],
        private(set) ?string $parsedBodyParameter = null,
        private(set) ?string $requestParameter = null,
        private(set) array $requestAttributes = [],
        private(set) array $additionalMiddleware = [],
    ) {}
}
