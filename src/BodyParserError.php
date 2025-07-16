<?php

declare(strict_types=1);

namespace Crell\HttpTools;

readonly class BodyParserError
{
    public function __construct(
        public string $message,
    ) {}
}
