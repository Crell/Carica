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
        $bodyParamName = null;
        $bodyType = null;

        $result = $request->getAttribute(RouteResult::class) ?? throw new RouteResultNotProvided();

        if ($result instanceof RouteSuccess) {
            $rParams = new \ReflectionFunction($result->action)->getParameters();

            $getAttribute = static fn (\ReflectionParameter $rParam)
                => ($rParam->getAttributes(ParsedBody::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) !== null;

            $rBodyParam = array_find($rParams, $getAttribute);

            if ($rBodyParam) {
                $bodyParamName = $rBodyParam->getName();
                $rType = $rBodyParam->getType();
                if (!$rType instanceof \ReflectionNamedType) {
                    // Using a union type is a dev error, hence 500.
                    return $this->responseBuilder->createResponse(
                        HttpStatus::ServerError,
                        sprintf('Only simple parameter types are supported on %s', $bodyParamName)
                    );
                }
                $bodyType = $rType->getName();
            }
        }

        if ($bodyParamName && $bodyType) {
            /** @var class-string $bodyType */
            $contentType = $request->getHeaderLine('content-type');

            $canParse = static fn(BodyParser $p) => $p->canParse($contentType, $bodyType);

            /** @var ?BodyParser $parser */
            $parser = array_find($this->parsers, $canParse);

            $parsed = $parser?->parse($contentType, $request->getBody()->getContents(), $bodyType);

            if ($parsed instanceof BodyParserError) {
                return $this->responseBuilder->badRequest($parsed->message);
            }
            if ($parsed !== null) {
                $request = $request
                    ->withParsedBody($parsed)
                    ->withAttribute(ParsedBody::class, $bodyParamName);
            }
        }

        return $handler->handle($request);
    }
}
