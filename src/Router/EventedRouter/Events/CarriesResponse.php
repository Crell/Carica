<?php

declare(strict_types=1);

namespace Crell\Carica\Router\EventedRouter\Events;

use Psr\Http\Message\ResponseInterface;

/**
 * Indicates an object that can have an associated response object.
 *
 * It is expected that event listeners will set the $response property,
 * indicating that the response has been derived and terminating
 * event propagation.  Then the client code can read the response
 * off of it.
 */
interface CarriesResponse
{
    public ?ResponseInterface $response { get; set; }
}
