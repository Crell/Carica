<?php

declare(strict_types=1);

namespace Crell\Carica;

enum HttpMethod: string
{
    case Head = 'HEAD';
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Delete = 'DELETE';
    case Options = 'OPTIONS';
}
