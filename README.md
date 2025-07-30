# Carica

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Carica is a collection of loosely coupled tools for working with the PSR HTTP stack.

*Carica* is the scientific name for the fig tree.

## Included tools

### HttpStatus

Yet another Enum listing the different typical HTTP response codes used.

### ResponseBuilder

ResponseBuilder is a simple convenience wrapper around the PSR-17 factory classes.  It provides a single, easy to use "builder" class that produces common PSR-7 response objects types.  You may bring your own PSR-17 factory of your choice.

See the [ResponseBuilder](src/ResponseBuilder.php) class, as its methods should be fairly self-explantory just from their names.

### CacheHeaderMiddleware

This zero-configuration middleware ensures that cache headers are stripped from requests/responses that should not have them, according to the HTTP spec.

### EnforceHeadMiddleware

Ensures that the response to a HEAD request has an empty body, even if one was incorrectly set.

### DefaultContentTypeMiddleware

Allows setting a default `content-type` and `accept` header value on incoming requests.  Useful for APIs that allow clients to not specify those headers, without code further on needing to account for it being missing.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form](https://github.com/Crell/Carica/security) rather than the issue queue.

## Credits

- [Larry Garfield][link-author]
- [All Contributors][link-contributors]

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
