<?php

declare(strict_types=1);

namespace Crell\HttpTools;

use Crell\HttpTools\Router\Router;
use Crell\HttpTools\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

class MockRouter implements Router
{
    public function __construct(public RouteResult $mockResult) {}

    public function route(ServerRequestInterface $request): RouteResult
    {
        return $this->mockResult;
    }
}
