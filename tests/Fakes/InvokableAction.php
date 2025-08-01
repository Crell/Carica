<?php

declare(strict_types=1);


namespace Crell\Carica\Fakes;

class InvokableAction
{
    public function __invoke(string $a): string
    {
        return __CLASS__;
    }
}
