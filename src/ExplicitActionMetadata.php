<?php

declare(strict_types=1);

namespace Crell\Carica;

readonly class ExplicitActionMetadata implements ActionMetadata
{
    /**
     * @param array<string, string> $parameterTypes
     * @param array<string, string> $requestAttributes
     * @param array<string|class-string> $additionalMiddleware
     * @param array<string, string[]> $uploadedFileParameters
     */
    public function __construct(
        private(set) array $parameterTypes = [],
        private(set) ?string $parsedBodyParameter = null,
        private(set) ?string $requestParameter = null,
        private(set) array $requestAttributes = [],
        private(set) array $additionalMiddleware = [],
        private(set) array $uploadedFileParameters = [],
        private(set) ?UserAuthentication $authentication = null,
        private(set) ?UserAuthorization $authorization = null,
    ) {}
}
