# middlewares/whoops

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to use [Whoops](https://github.com/filp/whoops) as error handler.

## Requirements

* PHP >= 5.6
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15](https://github.com/http-interop/http-middleware) middleware dispatcher ([Middleman](https://github.com/mindplay-dk/middleman), etc...)

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

## Options

#### `__construct(Whoops\Run $whoops = null, Whoops\Util\SystemFacade $system = null)`

Allows to provide a custom `Whoops\Run` instance. If it's not defined, creates an instance automatically. You can provide also the `SystemFacade` used by the `Run` instance, in order to implement a special behaviour with fatal errors.

#### `catchErrors(true)`

To catch not only throwable exceptions, but also php errors. This makes whoops to be registered temporary in order to capture the errors using `set_error_handler`. It's enabled by default so, to disable it you have to use `->catchErrors(false)`;

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/whoops.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/whoops/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/whoops.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/whoops.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/20d7a029-f575-4d2d-9d9a-9de9178ddedc.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/whoops
[link-travis]: https://travis-ci.org/middlewares/whoops
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/whoops
[link-downloads]: https://packagist.org/packages/middlewares/whoops
[link-sensiolabs]: https://insight.sensiolabs.com/projects/20d7a029-f575-4d2d-9d9a-9de9178ddedc
