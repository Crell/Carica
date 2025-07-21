<?php

declare(strict_types=1);

namespace Crell\HttpTools\Middleware;

use Crell\HttpTools\BodyParser;
use Crell\HttpTools\BodyParserError;
use Crell\HttpTools\HttpStatus;
use Crell\HttpTools\ParsedBody;
use Crell\HttpTools\ResponseBuilder;
use Crell\HttpTools\Router\RouteResult;
use Crell\HttpTools\Router\RouteResultNotProvided;
use Crell\HttpTools\Router\RouteSuccess;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ParsedBodyMiddleware implements MiddlewareInterface
{
    /**
     * @param BodyParser[] $parsers
     */
    public function __construct(
        private readonly ResponseBuilder $responseBuilder,
        private array $parsers = [],
    ) {}

    public function addParser(BodyParser $parser): self
    {
        $this->parsers[] = $parser;
        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class) ?? throw new RouteResultNotProvided();

        // We're doing a weak truthy check here, so that both null and '' values
        // result in not doing anything.
        if ($result instanceof RouteSuccess && $result->actionDef?->parsedBodyParameter && $result->actionDef->parameterTypes !== null) {
            /** @var class-string $bodyType */
            $bodyType = $result->actionDef->parameterTypes[$result->actionDef?->parsedBodyParameter];
            $contentType = $request->getHeaderLine('content-type');

            $parsed = $this
                ->getParser($contentType, $bodyType)
                ?->parse($contentType, $request->getBody()->getContents(), $bodyType);

            if ($parsed instanceof BodyParserError) {
                return $this->responseBuilder->badRequest($parsed->message);
            }
            if ($parsed !== null) {
                $request = $request
                    ->withParsedBody($parsed);
            }
        }

        return $handler->handle($request);
    }

    /**
     * @phpstan-param class-string $bodyType
     */
    private function getParser(string $contentType, string $bodyType): ?BodyParser
    {
        $canParse = static fn(BodyParser $p) => $p->canParse($contentType, $bodyType);

        return array_find($this->parsers, $canParse);
    }
}
