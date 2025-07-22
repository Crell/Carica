<?php

declare(strict_types=1);

namespace Crell\HttpTools\Fakes;

use Crell\HttpTools\Middleware;
use Crell\HttpTools\ParsedBody;
use Crell\HttpTools\Point;
use Crell\HttpTools\RequestAttribute;
use Psr\Http\Message\ServerRequestInterface;

class ActionExamples
{
    #[Middleware(TracingMiddleware::class)]
    public function oneExtraMiddleware(): string
    {
        return __FUNCTION__;
    }

    #[Middleware(TracingMiddleware::class)]
    #[Middleware(SecondMiddleware::class)]
    public function twoExtraMiddleware(): string
    {
        return __FUNCTION__;
    }

    public function allParameterParts(#[ParsedBody] Point $point, ServerRequestInterface $request, string $fromUrl, #[RequestAttribute('narf')] string $beep): string
    {
        return __FUNCTION__;
    }
}
