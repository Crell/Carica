<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ActionResultRenderer
{
    public function renderResponse(ServerRequestInterface $request, mixed $result): ResponseInterface;
}
