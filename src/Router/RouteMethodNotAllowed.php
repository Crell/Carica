<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

readonly class RouteMethodNotAllowed implements RouteResult
{
    /**
     * @param string[] $allowedMethods
     */
    public function __construct(public array $allowedMethods)
    {
    }
}
