<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Crell\HttpTools\ResponseBuilder;
use Crell\Serde\Serde;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A simple result renderer that assumes the response is JSON.
 */
readonly class JsonResultRenderer implements ActionResultRenderer
{
    public function __construct(
        private ResponseBuilder $responseBuilder,
        private ?Serde $serde = null,
    ) {}

    public function renderResponse(ServerRequestInterface $request, mixed $result): ResponseInterface
    {
        $body = match (true) {
            is_scalar($result) => (string)$result,
            is_object($result) && $this->serde !== null => $this->serde->serialize($result, 'json'),
            is_array($result), is_object($result) => json_encode($result, JSON_THROW_ON_ERROR),
            // Ignoring for code coverage, because there's no way to actually get here, but PHPStan insists we have it.
            default => throw new \LogicException('Unsupported result type: ' . get_debug_type($result)), // @codeCoverageIgnore
        };

        return $this->responseBuilder
            ->ok($body)
            ->withHeader('content-type', 'application/json');
    }
}
