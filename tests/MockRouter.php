<?php

declare(strict_types=1);

namespace Crell\Carica;

use Crell\Carica\Router\Router;
use Crell\Carica\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

class MockRouter implements Router
{
    public function __construct(public RouteResult $mockResult) {}

    public function route(ServerRequestInterface $request): RouteResult
    {
        return $this->mockResult;
    }
}
