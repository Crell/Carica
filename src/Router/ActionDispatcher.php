<?php

declare(strict_types=1);

namespace Crell\HttpTools\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Calls the final action that is responsible for this request.
 *
 * If the action result is not a Response, it will delegate to
 * the $resultRenderer service if defined. If not defined,
 * a basic HTTP 500 will be returned.
 */
readonly class ActionDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private ?ActionResultRenderer $resultRenderer = null,
        private ?LoggerInterface $logger = null,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RouteSuccess $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class) ?? throw new RouteResultNotProvided();

        $definedParams = $routeResult->actionDef->parameterTypes ?? [];
        $available = $routeResult->arguments;

        // Passing the request itself has to happen last, in case
        // previous middleware layers modified it.
        if ($routeResult->actionDef?->requestParameter) {
            $available[$routeResult->actionDef->requestParameter] = $request;
        }

        // If there is a parsed body, and an instruction of where to put it,
        // pass that in, too.
        if ($bodyParam = $routeResult->actionDef?->parsedBodyParameter) {
            $available[$bodyParam] = $request->getParsedBody();
        }

        // Call the action.
        $args = array_intersect_key($available, $definedParams);
        $result = ($routeResult->action)(...$args);

        if ($result instanceof ResponseInterface) {
            return $result;
        }
        $result = $this->resultRenderer?->renderResponse($request, $result);

        if ($result === null) {
            $this->logger?->error('The action handler returned a non-response and no renderer was provided.', [
                'path' => $request->getUri()->getPath(),
            ]);
            throw ActionResultNotRendered::create($request, $result);
        }

        return $result;
    }
}
