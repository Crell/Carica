<?php

declare(strict_types=1);

namespace Crell\Carica;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A simple middleware stack kernel.
 */
class StackMiddlewareKernel implements RequestHandlerInterface
{
    private RequestHandlerInterface $tip;

    /**
     * @param RequestHandlerInterface $baseHandler
     *   The "base" handler that will be the last-resort handler called.
     *   Usually this will be whatever logic dispatches to your action,
     *   but could be anything.
     * @param MiddlewareInterface[] $middleware
     *   An array of middleware to initialize the stack with.
     *   Those listed first will be the "outermost," so it will get
     *   the request first and response last.  Note: This is opposite to
     *   calling addMiddleware().
     */
    public function __construct(
        RequestHandlerInterface $baseHandler,
        array $middleware = [],
    ) {
        $this->tip = $baseHandler;

        foreach (array_reverse($middleware) as $m) {
            $this->tip = new PassthruHandler($m, $this->tip);
        }
    }

    /**
     * @param MiddlewareInterface $middleware
     *   Adds a middleware as the new outer "layer".  That means it will get the
     *   request before anything else already registered, and the response after.
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->tip = new PassthruHandler($middleware, $this->tip);
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->tip->handle($request);
    }
}
