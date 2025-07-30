<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ActionResultRenderer
{
    public function renderResponse(ServerRequestInterface $request, mixed $result): ResponseInterface;
}
