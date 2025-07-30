<?php

declare(strict_types=1);


namespace Crell\Carica\Middleware;

use Crell\Carica\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Converts any uncaught throwables to HTTP 500 errors.
 *
 * If a logger is provided, the exception will also be logged.
 *
 * This should generally be the outermost middleware.
 */
readonly class ExceptionCatcherMiddleware implements MiddlewareInterface
{
    /**
     * @param bool $debug
     *   If true, the response body will include information about the exception thrown.
     *   If false, the body will be empty to avoid leaking sensitive information to the user.
     */
    public function __construct(
        private ResponseBuilder $responseBuilder,
        private ?LoggerInterface $logger = null,
        private bool $debug = false,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $this->logger?->error('Uncaught exception in RestServer: {name}', [
                'name' => get_class($e),
                'exception' => $e,
            ]);
            if ($this->debug) {
                $message = sprintf('%s: %s, %s', $e->getMessage(), $e->getFile(), $e->getLine());
                return $this->responseBuilder->createResponse(500, $message);
            }

            return $this->responseBuilder->createResponse(500, '');
        }
    }
}
