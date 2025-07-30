<?php

declare(strict_types=1);

namespace Crell\Carica;

class Point implements HasX
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}
}
