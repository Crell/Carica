<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * A rudimentary logging middleware, using any PSR-3 logger.
 *
 * This should be an early-running middleware, if used. Note that
 * server logs are probably more efficient and useful than this one,
 * but when those are not available, this one works in a pinch.
 */
readonly class LogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->info('Request received to {path}', ['path' => $request->getUri()->getPath()]);
        return $handler->handle($request);
    }
}
