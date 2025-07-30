<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Calls the final action that is responsible for this request.
 *
 * If the action result is not a Response, it will delegate to
 * the $resultRenderer service if defined. If not defined, it will
 * throw an exception that should turn into an HTTP 500 error.
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
        $actionDef = $routeResult->actionDef;

        $available = $routeResult->arguments;

        if ($actionDef) {
            // Passing the request itself has to happen last, in case
            // previous middleware layers modified it.
            if ($actionDef->requestParameter) {
                $available[$actionDef->requestParameter] = $request;
            }

            // If there is a parsed body, and an instruction of where to put it,
            // pass that in, too.
            if ($bodyParam = $actionDef->parsedBodyParameter) {
                $available[$bodyParam] = $request->getParsedBody();
            }

            foreach ($actionDef->requestAttributes as $name => $target) {
                $available[$name] = $request->getAttribute($target);
            }

            foreach ($actionDef->uploadedFileParameters as $name => $tree) {
                $available[$name] = $this->getFile($request, $tree);
            }
        }

        // Call the action.
        $args = array_intersect_key($available, $actionDef->parameterTypes ?? []);
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

    /**
     * @param string[] $path
     */
    private function getFile(ServerRequestInterface $request, array $path): ?UploadedFileInterface
    {
        $files = $request->getUploadedFiles();
        $first = array_shift($path);
        $file = &$files[$first];
        foreach ($path as $segment) {
            $file = $file[$segment] ?? null;
        }
        return $file;
    }
}
