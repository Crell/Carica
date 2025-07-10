<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router\EventedRouter;

use Crell\HttpTools\Router\ActionResultNotRendered;
use Crell\HttpTools\Router\ActionResultRenderer;
use Crell\HttpTools\Router\EventedRouter\Events\HandleRenderResult;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles rendering a route result by passing it to an event dispatcher.
 *
 * The event fired is Crell\HttpTools\Router\EventedRouter\Events\HandleRenderResult.
 * It contains the request and action result, and can have a response set on it.
 * Setting the response will terminate the event.
 *
 * @see https://github.com/Crell/Tukio
 * @see HandleRenderResult
 */
readonly class EventedResultRenderer implements ActionResultRenderer
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function renderResponse(ServerRequestInterface $request, mixed $result): ResponseInterface
    {
        /** @var HandleRenderResult $event */
        $event = $this->dispatcher->dispatch(new HandleRenderResult($request, $result));
        return $event->response
            ?? throw ActionResultNotRendered::create($request, $result);
    }
}
