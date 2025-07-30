<?php

declare(strict_types=1);

namespace Crell\Carica;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A convenience wrapper around PSR-17 to makae Response objects.
 *
 * It is compatible with any conforming PSR-17 ResponseFactory and StreamFactory.
 *
 * Method descriptions are derived from the Mozilla Developer portal.
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status
 */
class ResponseBuilder
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Creates an arbitrary response.
     */
    public function createResponse(int|HttpStatus $code, string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        if ($code instanceof HttpStatus) {
            $code = $code->value;
        }
        if (is_string($body)) {
            $body = $this->streamFactory->createStream($body);
            $body->rewind();
        }
        $response = $this->responseFactory
            ->createResponse($code)
            ->withBody($body);
        if ($contentType) {
            $response = $response->withHeader('content-type', $contentType);
        }

        return $response;
    }

    /**
     * Creates an HTTP 200 response.
     *
     * The request succeeded. The result and meaning of "success" depends on the HTTP method:
     */
    public function ok(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(HttpStatus::OK, $body, $contentType);
    }

    /**
     * Creates an HTTP 201 response.
     *
     * The request succeeded, and a new resource was created as a result. This is typically the response sent
     * after POST requests, or some PUT requests.
     */
    public function created(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::Created->value)
            ->withHeader('location', $location);
    }

    /**
     * Creates an HTTP 204 response.
     *
     * There is no content to send for this request, but the headers are useful.
     * The user agent may update its cached headers for this resource with the new ones.
     */
    public function noContent(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::NoContent->value);
    }

    /**
     * Creates an HTTP 303 redirect response.
     *
     * The server sent this response to direct the client to get the
     * requested resource at another URI with a GET request.
     */
    public function seeOther(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::SeeOther->value)
            ->withHeader('location', $location);
    }

    /**
     * Creates an HTTP 304 response.
     *
     * This is used for caching purposes. It tells the client that the response has not been modified,
     * so the client can continue to use the same cached version of the response.
     */
    public function notModified(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::NotModified->value);
    }

    /**
     * Creates an HTTP 307 Temporary redirect.
     *
     * The server sends this response to direct the client to get the requested resource at another
     * URI with the same method that was used in the prior request. This has the same semantics
     * as the 302 Found response code, with the exception that the user agent must not change
     * the HTTP method used: if a POST was used in the first request, a POST
     * must be used in the redirected request.
     *
     * Generally you should use this response instead of a 302.
     */
    public function temporaryRedirect(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::TemporaryRedirect->value)
            ->withHeader('location', $location);
    }

    /**
     * Creates an HTTP 308 Permanent redirect.
     *
     * This means that the resource is now permanently located at another URI, specified by the
     * Location response header. This has the same semantics as the 301 Moved Permanently
     * HTTP response code, with the exception that the user agent must not change the HTTP
     * method used: if a POST was used in the first request, a POST must be used in the second request.
     *
     * Generally you should use this response instead of a 301.
     */
    public function permanentRedirect(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::PermanentRedirect->value)
            ->withHeader('location', $location);
    }

    /**
     * Creates an HTTP 400 client error.
     *
     * The server cannot or will not process the request due to something that is perceived to be a
     * client error (e.g., malformed request syntax, invalid request message framing, or deceptive request routing).
     */
    public function badRequest(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(HttpStatus::BadRequest->value, $body, $contentType);
    }

    /**
     * Creates an HTTP 401 client error.
     *
     * Although the HTTP standard specifies "unauthorized", semantically this response means "unauthenticated".
     * That is, the client must authenticate itself to get the requested response.
     */
    public function unauthorized(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(HttpStatus::Unauthorized->value, $body, $contentType);
    }

    /**
     * Creates an HTTP 403 client error.
     *
     * The client does not have access rights to the content; that is, it is unauthorized,
     * so the server is refusing to give the requested resource. Unlike 401 Unauthorized,
     * the client's identity is known to the server.
     */
    public function forbidden(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(HttpStatus::Forbidden->value, $body, $contentType);
    }

    /**
     * Creates an HTTP 404 client error.
     *
     * The server cannot find the requested resource. In the browser, this means the URL is
     * not recognized. In an API, this can also mean that the endpoint is valid but the
     * resource itself does not exist. Servers may also send this response instead of
     * 403 Forbidden to hide the existence of a resource from an unauthorized client.
     */
    public function notFound(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(HttpStatus::NotFound->value, $body, $contentType);
    }

    /**
     * Creates an HTTP 405 client error.
     *
     * The request method is known by the server but is not supported by the target resource. For example,
     * an API may not allow DELETE on a resource, or the TRACE method entirely.
     *
     * @param string[] $allowedMethods
     */
    public function methodNotAllowed(array $allowedMethods): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::MethodNotAllowed->value)
            ->withHeader('allow', implode(', ', array_map(strtoupper(...), $allowedMethods)))
            ;
    }

    /**
     * Creates an HTTP 410 client error.
     *
     * This response is sent when the requested content has been permanently deleted from server,
     * with no forwarding address. Clients are expected to remove their caches and links to the
     * resource. The HTTP specification intends this status code to be used for "limited-time,
     * promotional services". APIs should not feel compelled to indicate resources that have
     * been deleted with this status code.
     */
    public function gone(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(HttpStatus::Gone->value, $body, $contentType);
    }

    /**
     * Creates an HTTP 415 client error.
     *
     * The media format of the requested data is not supported by the server, so the server is rejecting the request.
     *
     * @param string[] $allowedTypes
     */
    public function unsupportedMediaType(array $allowedTypes): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(HttpStatus::UnsupportedMediaType->value)
            ->withHeader('accept', implode(', ', $allowedTypes))
            ;
    }
}
