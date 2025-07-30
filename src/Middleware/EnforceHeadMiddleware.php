<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This is to be in compliance with RFC 2616, Section 9.
 *
 * If the incoming request method is HEAD, we need to ensure that the response body
 * is empty.
 * https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
 */
class EnforceHeadMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $method = strtoupper($request->getMethod());
        if ($method === 'HEAD') {
            $emptyBody = $this->streamFactory->createStream();
            return $response->withBody($emptyBody);
        }

        return $response;
    }
}
