<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Psr\Http\Message\ServerRequestInterface;

class DelegatingRouter implements Router
{
    /** @var array<Router> */
    private array $delegates = [];

    public function __construct(
        private readonly Router $default,
    ) {}

    public function delegateTo(string $prefix, Router $router): void
    {
        $this->delegates[$prefix] = $router;
    }

    public function route(ServerRequestInterface $request): RouteResult
    {
        $registeredPrefixes = array_keys($this->delegates);

        foreach ($this->getPrefixes($request->getUri()->getPath()) as $requestPrefix) {
            if (in_array($requestPrefix, $registeredPrefixes, true)) {
                $result = $this->delegates[$requestPrefix]->route($request);
                if (! $result instanceof RouteNotFound) {
                    return $result;
                }
            }
        }

        return $this->default->route($request);
    }

    /**
     * @return string[]
     *   An array of chunks of the path to check against.
     */
    private function getPrefixes(string $requestPath): array
    {
        if (str_contains($requestPath, '.')) {
            [$normalizedPath, $ext] = \explode('.', $requestPath);
        } else {
            $normalizedPath = $requestPath;
        }

        $pathParts = \explode('/', \trim($normalizedPath, '/'));

        $prefixes = array_reverse(array_reduce($pathParts, $this->reducer(...), []));
        $prefixes[] = '/';
        return $prefixes;
    }

    /**
     * @param string[] $carry
     * @return string[]
     */
    private function reducer(array $carry, string $item): array
    {
        $carry[] = end($carry) . '/' . $item;
        return $carry;
    }
}
