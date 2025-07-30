<?php

declare(strict_types=1);

namespace Crell\Carica\Fakes;

class ActionUnionTypeExample
{
    public function hasUnions(string|int $test): string
    {
        return __FUNCTION__;
    }
}
