<?php

declare(strict_types=1);

namespace Crell\Carica\Router\EventedRouter;

use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\EventedRouter\Events\HandleMethodNotAllowed;
use Crell\Carica\Router\RouteMethodNotAllowed;
use Crell\Carica\Router\RouteResult;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Method-not-allowed middleware that delegates handling to a PSR-14 event dispatcher.
 *
 * The event fired is Crell\Carica\Router\EventedRouter\Events\HandleMethodNotAllowed.
 * It contains the request, and can have a response set on it. Setting the
 * response will terminate the event.
 *
 * @see https://github.com/Crell/Tukio
 * @see HandleMethodNotAllowed
 */
readonly class EventedMethodNotAllowedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ResponseBuilder $responseBuilder,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // If $result is null for some reason, this middleware will silently do nothing.
        $result = $request->getAttribute(RouteResult::class);
        if ($result instanceof RouteMethodNotAllowed) {
            /** @var HandleMethodNotAllowed $event */
            $event = $this->dispatcher->dispatch(new HandleMethodNotAllowed($request));
            return $event->response ?? $this->responseBuilder->notFound('Not Found', 'text/plain');
        }

        return $handler->handle($request);
    }
}
