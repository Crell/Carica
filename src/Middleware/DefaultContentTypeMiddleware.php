<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sets default content-type and accept headers.
 *
 * Some APIs will always be used with a certain format (usually application/json), so allow
 * clients to omit the content-type or accept headers.  This middleware adds defaults to a request
 * so that later processing can rely on those headers always being set.
 */
class DefaultContentTypeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ?string $contentType = null,
        private readonly ?string $acceptType = null,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->contentType && !$request->getHeaderLine('content-type')) {
            $request = $request->withHeader('content-type', $this->contentType);
        }
        if ($this->acceptType && !$request->getHeaderLine('accept')) {
            $request = $request->withHeader('accept', $this->acceptType);
        }

        return $handler->handle($request);
    }
}
