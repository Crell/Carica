<?php

declare(strict_types=1);

namespace Crell\Carica\Router;

use Psr\Http\Message\ServerRequestInterface;

class ActionResultNotRendered extends \LogicException
{
    public readonly ServerRequestInterface $request;
    public readonly mixed $result;

    public static function create(ServerRequestInterface $request, mixed $result): self
    {
        $new = new self();
        $new->request = $request;
        $new->result = $result;
        $new->message = sprintf("No renderer available for the action result.");

        return $new;
    }
}
