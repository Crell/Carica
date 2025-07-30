<?php

declare(strict_types=1);

namespace Crell\Carica\Router\EventedRouter\Events;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HandleMethodNotAllowed implements StoppableEventInterface, CarriesResponse
{
    public ?ResponseInterface $response = null;

    public function __construct(
        public readonly ServerRequestInterface $request,
    ) {}

    public function isPropagationStopped(): bool
    {
        return isset($this->response);
    }
}
