<?php

declare(strict_types=1);

namespace Crell\Carica;

enum HttpStatus: int
{
    case OK = 200;
    case Created = 201;
    case Accepted = 202;
    case NoContent = 204;
    case ResetContent = 205;

    case SeeOther = 303;
    case NotModified = 304;
    case TemporaryRedirect = 307;
    case PermanentRedirect = 308;

    case BadRequest = 400;
    case Unauthorized = 401;
    case PaymentRequired = 402;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    /** Indicates the server cannot handle the Accept header */
    case NotAcceptable = 406;
    case Conflict = 409;
    case Gone = 410;
    /** Indicates the server cannot handle the Content-Type header */
    case UnsupportedMediaType = 415;
    case Teapot = 418;
    case TooManyRequests = 429;
    case LegalReasons = 451;

    case ServerError = 500;
    case NotImplemented = 501;
    case ServiceUnavailable = 503;
}
