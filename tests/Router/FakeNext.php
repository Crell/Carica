<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FakeNext implements RequestHandlerInterface
{
    private(set) ?ServerRequestInterface $request = null;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $routeResult = $request->getAttribute(RouteResult::class);
        if ($routeResult === null) {
            return new Response(500, body: 'no route info');
        }
        return match ($routeResult::class) {
            RouteSuccess::class => new Response(200, body: 'from next'),
            RouteNotFound::class => new Response(404, body: 'from next'),
            RouteMethodNotAllowed::class => new Response(405, body: 'from next'),
            default => throw new \Exception('Incomprehensible result.'),
        };
    }
}
