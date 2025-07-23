<?php

declare(strict_types=1);

namespace Crell\HttpTools\Fakes;

class ActionUnionTypeExample
{
    public function hasUnions(string|int $test): string
    {
        return __FUNCTION__;
    }
}
