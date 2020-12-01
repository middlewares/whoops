# middlewares/whoops

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-ga]
[![Total Downloads][ico-downloads]][link-downloads]

Middleware to use [Whoops](https://github.com/filp/whoops) as error handler.

## Requirements

* PHP >= 7.2
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/whoops](https://packagist.org/packages/middlewares/whoops).

```sh
composer require middlewares/whoops
```

## Example

```php
$dispatcher = new Dispatcher([
    new Middlewares\Whoops()
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## Usage

The constructor accepts a `Whoops\Run` instance but creates one automatically if it's not provided. Optionally, you can provide a `Psr\Http\Message\ResponseFactoryInterface` as the second argument to create the response. If it's not defined, [Middleware\Utils\Factory](https://github.com/middlewares/utils#factory) will be used to detect it automatically.

```php
$whoops = new Whoops\Run();
$responseFactory = new MyOwnResponseFactory();

//Create a Run instance automatically
$middleware = new Middlewares\Whoops();

//Pass your own Run instance
$middleware = new Middlewares\Whoops($whoops);

//Pass a Run instance and ResponseFactory
$middleware = new Middlewares\Whoops($whoops, $responseFactory);
```

### catchErrors

To catch not only throwable exceptions, but also php errors. This makes whoops to be registered temporary in order to capture the errors using `set_error_handler`. It's enabled by default so, to disable it you have to use `->catchErrors(false)`;

```php
//Do not catch errors
$middleware = (new Middlewares\Whoops())->catchErrors(false);
```

### handlerContainer

This option allows to define a custom PSR-11 container used to create the intance of `Whoops\Handler\HandlerInterface` based in the `Accept` header in the request.

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/whoops.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ga]: https://github.com/middlewares/whoops/workflows/testing/badge.svg
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/whoops.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/whoops
[link-downloads]: https://packagist.org/packages/middlewares/whoops
