# Carica HTTP framework

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Carica is a collection of loosely coupled tools for working with the PSR HTTP stack.

It includes a robust routing bridge that allows using any arbitrary router, but then supports mapping different values directly to the action for each route.  It can map placeholders from the route itself, HTTP query arguments, arbitrary `ServerRequest` attributes, the parsed body of the request (it will parse it for you, too), and even uploaded files.  It can even convert route parameters into
a loaded object, just by examining the type signature of the action.

Similarly, the action method may return a `ResponseInterface` object, or any other value it chooses.  That value can then be converted into a `ResponseInterface` by an `ActionResultRenderer` that you provide.  A "turn it all into JSON" one is provided, but it's straightforward to write your own.

All of these features are commonly found only in full stack frameworks, that require you to buy into their specific way of working.  Carica is just a series of [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware designed to work together.  They can be easily mixed-and-matched into whatever system you want, with or without any of the above-mentioned features.

For simple "I just want something that runs" usage, use [`StandardApplication`](src/StandardApplication.php).  With a few constructor parameters, it will give you a fully running system.  Or, clone it and modify to suit your needs.  Or use it as a guide to wire up your DI container.  It is entirely up to you.

*Carica* is the scientific name for the fig tree.

## Usage

### Basic Setup

We'll assume that you're using the `StandardApplication`, which comes with all the bells and whistles.  The particular bells and whistles are listed further below, and if you make your own custom configuration you may leave out any particular bell or whistle if desired.

```php
// More on this in a moment.
$router = make_a_router();

$app = new \Crell\Carica\StandardApplication(
responseFactory: $aPSR17ResponseFactory,
streamFactory: $aPsr17StreamFactory,
router: $router,
);
```

That is all that is needed to set up a basic JSON-based application.  There are several more optional arguments as documented in the code.  Most notably, it is strongly recommended that you provide a `$container` (any PSR-11 `ContainerInterface`), and you may want to provide `$parameterLoaders`.

### Actions and parameters

Now the action.  An "action" in Carica parlance is whatever callable is responsible for handling this specific request.  Some systems call these "controllers," others use "controller" to refer to a class with a bunch of action methods.  In Carica, it's whatever Closure is returned by the Router; function, method, invokable class, we don't care.

For argument's sake, let's assume it's the `__invoke()` method of a class, and that class has various dependencies, and the class is wired into a DI container so that those dependencies get populated.  (This is the recommended usage.)

Consider the following, and assume it's been wired into the router as `POST: /foo/{bar}/baz/{qix}`, and then the request is `POST /foo/1/baz/beep?narf=poink`.

```php
use Crell\Carica\ParsedBody;
use Crell\Carica\RequesteAttribute;

class SomeAction
{
    public function __construct(
        private ServiceA $serviceA,
        private ServiceB $serviceB,
    ) {}
    
    public __invoke(
        #[ParsedBody] 
        Message $body, 
        Bar $bar,
        string $qix,
        ServerRequestInterface $request,
        #[RequestAttribute('some_known_key')]
        string $someValue,
        string $narf = 'default', 
    ): array {
        // Do some logic, using ServiceA and ServiceB.
        
        return ['result' => 'data'];
    }
}
```

There's a lot going on here, so let's take it one by one.  The order of parameters in the action has no meaning.

#### `#[ParsedBody]`

There may be at most one parameter tagged `#[ParsedBody]`.  If so, the body of the incoming request will be parsed either into an array or into the class specified.  By default, this is done using [Crell/Serde](https://github.com/Crell/Serde), which is an optional dependency.  You should really use it, though.  However, the body parsing logic is pluggable (it's just a dependency for the corresponding middleware), so if you'd rather use something else, you are free to do so.

#### `$qix`

These parameters line up with placeholder names in the route, so the router is expected to extract those values and make them available.  They will then be passed to the function by name.  They will also be automatically cast to the correct type: `int`, `float`, `string`, or `bool`.  For booleans, the values `1`, `"1"`, `"true"`, `"yes"`, and `"on"` will all be interpreted as `true`.  The values `0`, `"0"`, `"false"`, `"no"`, and `"off"` will be interpreted as `false`.  All other values will result in an error (by default an HTP 400).  They may have default values, in which case they are optional.

#### `$narf`

HTTP Query parameters may be passed directly, too.  They also are matched by name.  That does mean that a placeholder and query parameter may not have the same name.  As shown here, they may have default values in which case they are optional.  (This is recommended for HTTP query parameters.)

#### `Bar $bar`

Alternatively, a parameter may be typed as some class.  In that case, the system needs you to provide one or more Parameter Loaders.  `StandardApplication` offers a parameter to provide them, but as they will likely have their own dependencies as well (often a database), at that point it's probably best to wire up Carica with your DI container instead.  If a placeholder or HTTP query parameter is typed to an object, the Loaders will be called to load the object of that type that corresponds to the found argument.  That means in this case, we'll be passed a `Bar` instance that corresponds to the ID `1`.

#### `ServerRequestInterface`

Optionally, the entire request may be passed to the action.  Usually this is not necessary, but it's available.  Any one parameter typed to `ServerRequestInterface` will be passed the request to do with as you please.

#### `#[RequestAttribute]`

A PSR-7 ServerRequest object includes "request attributes," which are arbitrary additional metadata.  These may be used for a variety of purposes, including routing, authentication, or other sorts of context.  Any parameter tagged with `#[RequestAttribute]` will be passed the request attribute of the same name.  Alternatively, you may provide the name of the request attribute if you want to use a different name for the variable.  (Often request attributes are keyed by a class name, which doesn't work as a variable name.)  This may also have a default value if desired.

#### `#[File]`

Not shown here (as it makes little sense to use at the same time as `#[ParsedBody]`), the `#[File]` attribute may be tagged on any paramter typed to PSR-7's `UploadedFileInterface`.  It will then be passed the uploaded file from the request that corresponds to the parameter name.  Alternatively, `File` takes a single argument, which can be either a string or an array.  If an array, it assumes you are using nested field names (something PHP supports), and the array elements are the "tree" of array levels down to the file.  (See [PSR-7](https://www.php-fig.org/psr/psr-7/#16-uploaded-files) for more details, as they're a little involved, and the [`File` attribute](src/File.php) for corresponding examples.)

#### Action-specific middleware

Additionally, the action itself may specify additional middleware that should run, just for that action.

```php
use Crell\Carica\RequesteAttribute;
use Crell\Carica\Middleware;

class SomeAction
{
    public function __construct(
        private ServiceA $serviceA,
        private ServiceB $serviceB,
    ) {}
    
    #[Middleware(SomeMiddleware::class), Middleware('service_id')]
    public __invoke(int $foo, string $bar): array 
    {
        // Do some logic, using ServiceA and ServiceB.
        
        return ['result' => 'data'];
    }
}
```

In this case, if a container has been provided, the services with IDs `SomeMiddleware::class` and `service_id` will be invoked, in that order, immediately before the action runs.  They can do whatever they need, as any other middleware.

If a container is not provided or the service ID is not found, Carica will treat it as a class name and try to instantiate it without any constructor arguments.  If the string is not a valid class name or the class has required constructor arguments, PHP will throw an Error that will be converted to an HTTP 500.

### Action return values

An action may return a PSR-7 `ResponseInterface` object, in which case that object is what will be used (give or take any other middleware).  However, it is also free to return any value of any type.  If a non-Response is returned, and a `ActionResultRenderer` has been configured, that service is responsible for turning whatever it is into a Response object.  Out of the box, `StandardApplication` uses a renderer that will use Crell/Serde to serialize any array or object to JSON, and treat any scalar object as though it's already a valid JSON string.  Other alternatives include rendering a result object using an HTML template engine, or even varying the behavior based on the `Accept` header of the request.

### Emitting the response

Carica only goes as far as producing the response object.  Sending it to the client is the job of an "Emitter."  There are various emitter implementations on the market, but as that is not part of any formal PSR specification Carica does not directly leverage any.

If you are not sure what to use, we recommend the [httpsoft/http-emitter](https://packagist.org/packages/httpsoft/http-emitter) package.

## Routing

Notably, one important aspect of the above design is that it is router independent, but relies on the Router to do a fair bit of lifting.  By design, a router can be any class that implements the `Router` interface, which in practice will likely be small bridges that connect to an existing routing library.  The provided [`FastRouteRouter`](src/Router/FastRouteRouter.php) bridge serves as an example.

There is no standard mechanism for configuring a router, nor even a standard syntax.  (Actually there is an IETF standard, but most PHP routers don't use it.)  Therefore, Carica does not provide any route configuration system.  Every router implementation will need its own, likely integrated with your DI container or other bootstrap logic.

Importantly, routers MUST NOT throw exceptions.  In case a route is not found, that's a normal return.  That allows for a trivially simple [`DelegatingRouter`](src/Router/DelegatingRouter.php), which allows stitching together multiple routers in serial, and allowing each to make an attempt to handle routing.  If one cannot, control passes to the next until either a router can handle it, or it just resolves into a NotFound case.

## Generic middleware and tools

Carica includes a number of middleware and related tools that are generically useful, in this or any other application.  The `StandardApplication` makes use of them, but they are optional.

### HttpStatus

Yet another Enum listing the different typical HTTP response codes used.

### HttpMethod

Yet another Enum listing the different typical HTTP methods used.

### `ResponseBuilder`

ResponseBuilder is a simple convenience wrapper around the [PSR-17](https://www.php-fig.org/psr/psr-17/) factory classes.  It provides a single, easy to use "builder" class that produces common PSR-7 response objects types.  You may bring your own PSR-17 factory of your choice.

See the [ResponseBuilder](src/ResponseBuilder.php) class, as its methods should be fairly self-explanatory just from their names.

ResponseBuilder is a dependency of several other middleware listed below.

### `StackMiddlewareKernel`

This simple stack builder wires together a series of middleware.  Middleware may be provided in the constructor as an array, in an "outside in" order.  That is, the first middleware listed will get the request first, and the response last.  That allows it to be read "down" the array.  Middleware may also be added using the `addMiddleware()` method, which will add a new middleware as an outer layer, meaning it will get the request before anything previously specified.

### `CacheHeaderMiddleware`

This zero-configuration middleware ensures that cache headers are stripped from requests/responses that should not have them, according to the HTTP spec.

### `DefaultContentTypeMiddleware`

Allows setting a default `content-type` and `accept` header value on incoming requests.  Useful for APIs that allow clients to not specify those headers, without code further on needing to account for it being missing.

### `EnforceHeadMiddleware`

Ensures that the response to a HEAD request has an empty body, even if one was incorrectly set.

### `ExceptionCatcherMiddleware`

A simple, no-frills exception middleware.  This should generally be the outermost middleware in the stack.  It also supports a toggle to show more or less information about an exception when converting it to an HTTP 500 server error.  If a PSR-3 logger is provided, it will also log the exception there.

### `LogMiddleware`

Normally you want to use server logs for general purpose request logging.  However, in a pinch you can also use this middleware to log any incoming requests using a [PSR-3](https://www.php-fig.org/psr/psr-3/) logger of your choice.

## Routing and Action middleware

The core of Carica is a suite of middleware designed to work on a [`RouteResult`](src/Router/RouteResult.php) object.  A `RouteResult` is what it says on the tin: It's the result of the routing process.  Any Carica router must implement a simple `Route` interface that returns a `RouteResult`.  There are three types of result:

* `RouteNotFound` - The route doesn't exist.
* `RouteMethodNotAllowed` - The route exists, but not for the provided method.
* `RouteSuccess` - The route exists, and here's gobs of information about it.

At minimum, [`RouteSuccess`](src/Router/RouteSuccess.php) includes a Closure that is the action that corresponds to the route, and any arguments extracted from the route path.  (It is required to be a Closure rather than a `callable` to keep the type consistent for later steps.)

It may optionally include an `ActionMetadata` object, which tells the rest of the system how the result information should get mapped to the action.  If not provided, the `DeriveActionMetadataMiddleware` will fill it in based on attributes on the action, as in the examples above.  That allows a compiled router to pre-compute that data and store it, avoiding any runtime overhead, while simpler use cases can still compute the data on the fly if needed.  (Which technically means a router could choose to not use the attributes and derive all the information in its own way.  That is certainly possible, but not recommended.)

The following middleware all rely on a `RouteResult` object, and should be stacked in the following order.  However, some may be omitted if desired.

### `RouterMiddleware`

This middleware must be provided a `Router` instance, and will simply assign the returned route result to a request attribute.  Optionally, it may also be given request handlers for `RouteNotFound` and `RouteMethodNotAllowed` cases.  If it is, those will be called as appropriate and must return an appropriate Response object.  If not, handling those cases is left to later middleware.

### `GenericNotFoundMiddleware`

If the router resulted in a Not Found, this middleware will convert that into an HTTP 404 response with empty body.  It's a reasonable default, especially for an API, but HTML based systems will likely want to have a more robust error page instead.  (That could be implemented either as a middleware or as a Request Handler, which would then be provided directly to `RouterMiddleware`).

### `GenericMethodNotAllowedMiddleware`

If the router resulted in a Method Not Allowed, this middleware will convert that into an HTTP 405 response with empty body, but with the requisite `allow` header to specify what HTTP methods are permitted, based on the data in the `RouteMethodNotAllowed` object returned by the Router.

Additionally, if the HTTP request is for `OPTIONS`, it will handle responding to that with the provided data.

### `DeriveActionMetadataMiddleware`

If the `RouteSuccess` object does not include the necessary metadata about the route, this middleware will derive it off of the attributes of the action itself.  It uses the [`Crell/AttributeUtils`](https://github.com/Crell/Serde) library.  If you can guarantee that the metadata will always be included with the route, this middleware may be omitted.  (But it's probably best to keep it just in case.)

### `QueryParametersMiddleware`

This middleware is responsible for merging any provided query parameters into the arguments list, which makes them available to the action.  If you do not want that functionality for some reason, simply omit this middleware.

### `NormalizeArgumentTypesMiddleware`

This middleware is responsible for ensuring the incoming arguments are of a type that matches the types defined in the action signature.  It can natively map string, int, float, and bool types, as described above.

Additionally, if it is configured with one or more `ParameterLoader` objects, it will delegate to those to handle converting primitives into objects automatically.  Every provided loader is keyed by a class or interface name.  The middleware will search each one in order and see if it's keyed to handle the type of the action parameter.  (That means a single loader can handle multiple types, via inheritance or interfaces.)  Whichever one says it can first, and successfully returns an object, will be used.

If any parameter cannot be converted into the specified type, it will generate an HTTP 400 response.

This middleware is not optional, as otherwise any action parameter typed something other than `string` will cause a type error.  Parameter Loaders are optional, however.

### `ParsedBodyMiddleware`

This middleware is responsible for hydrating the request body.  Specifically, it only acts if there is a parameter marked as requesting the parsed body.  If so, it will loop through a series of `BodyParser` instances with which it is configured.  The first one that indicates it is able to handle that type will be used, and will return an object that will be assigned as the `parsedBody` of the request.

One `BodyParser` implementation is included, which can handle JSON, YAML, TOML, and CSV request bodies and parse them using Crell/Serde into the specified class.  That should be sufficient for the vast majority of cases.

If you do not need a parsed body passed to your action, ever, then you may omit this middleware.  (But really, this is the fun part.  You probably do want this functionality.)

### `AdditionalMiddlewareMiddleware`

This redundantly-named middleware allows an action to specify additional `MiddlewareInterface` instances that should be run.  It should be the last middleware
to run before the action dispatcher.

### `ActionDispatcher`

Every PSR-15 stack "bottoms out" eventually into a `RequestHandlerInterface`.  That could be a default action, or anything else.  In the case of Carica, the bottom handler is `ActionDispatcher`.  `ActionDispatcher` takes all the information built up in the `RouteSuccess` instance by previous middleware and uses that to call the specified action, with the appropriate arguments.  If any arguments are provided that the action doesn't need, they will simply be ignored.

If the action returns a non-Response object, `ActionDispatcher` will call out to a provided `ActionResultRenderer` object, which is responsible for turning whatever that result is into a Response object.

This is effectively required, as otherwise the `RouteSuccess` object won't be able to do anything.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form](https://github.com/Crell/Carica/security) rather than the issue queue.

## Credits

- [Larry Garfield][link-author]
- [All Contributors][link-contributors]

Development of this package was sponsored by [MakersHub](https://makershub.ai/).

## License

The Lesser GPL version 3 or later. Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/Crell/Carica.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/License-LGPLv3-green.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Crell/Carica.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Crell/Carica
[link-scrutinizer]: https://scrutinizer-ci.com/g/Crell/Carica/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Crell/Carica
[link-downloads]: https://packagist.org/packages/Crell/Carica
[link-author]: https://github.com/Crell
[link-contributors]: ../../contributors
