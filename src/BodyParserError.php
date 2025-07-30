<?php

declare(strict_types=1);

namespace Crell\Carica;

/**
 * @codeCoverageIgnore
 */
readonly class BodyParserError
{
    public function __construct(
        public string $message,
    ) {}
}
