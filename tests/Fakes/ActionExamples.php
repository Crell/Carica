<?php

declare(strict_types=1);

namespace Crell\Carica\Fakes;

use Crell\Carica\Middleware;
use Crell\Carica\ParsedBody;
use Crell\Carica\Point;
use Crell\Carica\RequestAttribute;
use Crell\Carica\File;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

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

    public function allParameterParts(
        #[ParsedBody] Point $point,
        ServerRequestInterface $request,
        string $fromUrl,
        #[RequestAttribute('narf')] string $beep,
        #[File('myfile')] UploadedFileInterface $file,
    ): string
    {
        return __FUNCTION__;
    }
}
