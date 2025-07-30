<?php

declare(strict_types=1);

namespace Crell\Carica\Middleware;

use Crell\Carica\BodyParser;
use Crell\Carica\BodyParserError;
use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteResultNotProvided;
use Crell\Carica\Router\RouteSuccess;
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
        if ($result instanceof RouteSuccess && $result->actionDef?->parsedBodyParameter) {
            /** @var class-string|'array' $bodyType */
            $bodyType = $result->actionDef->parameterTypes[$result->actionDef->parsedBodyParameter];
            $contentType = $request->getHeaderLine('content-type');

            $previousParsedBody = $request->getParsedBody();
            $contents = $request->getBody()->getContents();

            if (is_object($previousParsedBody)) {
                // It's already parsed, we do nothing.
                return $handler->handle($request);
            }

            if ($bodyType === 'array') {
                // It's already an array, we do nothing.
                // If the parsed body isn't an array, this will error out later.
                return $handler->handle($request);
            }

            if (is_array($previousParsedBody)) {
                $contentType = BodyParser::PhpArrayType;
                $contents = $previousParsedBody;
            }

            $parsed = $this
                ->getParser($contentType, $bodyType)
                ?->parse($contentType, $contents, $bodyType);

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
