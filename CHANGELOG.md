# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [2.0.2] - 2022-01-27
### Added
- Support for psr/container 2.0 [#11].

## [2.0.1] - 2020-12-02
### Added
- Support for PHP 8

## [2.0.0] - 2019-11-30
### Added
- Allow to define a `responseFactory` in the second argument of the constructor

### Changed
- `getWhoopsInstance` is now protected, to allow override [#7]

### Removed
- Support for PHP 7.0 and 7.1
- The second argument of the constructor to pass a instance of `SystemFacade`.

## [1.2.0] - 2018-08-04
### Added
- PSR-17 support
- New option `responseFactory`

## [1.1.0] - 2018-05-23
### Added
- New option `handlerContainer` to use a PSR-11 container to customize the `Whoops\Handler\HandlerInterface` used to display the errors. [#5]

## [1.0.0] - 2018-01-26
### Added
- Improved testing and added code coverage reporting
- Added tests for PHP 7.2

### Changed
- Upgraded to the final version of PSR-15 `psr/http-server-middleware`

### Fixed
- Updated license year

## [0.6.0] - 2017-11-13
### Changed
- Replaced `http-interop/http-middleware` with  `http-interop/http-server-middleware`.

### Removed
- Removed support for PHP 5.x.

## [0.5.0] - 2017-09-21
### Changed
- Append `.dist` suffix to phpcs.xml and phpunit.xml files
- Changed the configuration of phpcs and php_cs
- Upgraded phpunit to the latest version and improved its config file
- Updated to `http-interop/http-middleware#0.5`

## [0.4.1] - 2017-06-26
### Fixed
- Fixed shutdown errors handling

## [0.4.0] - 2017-03-18
### Added
- Set the response content-type when an error is handled

## [0.3.0] - 2016-12-26
### Changed
- Updated tests
- Updated to `http-interop/http-middleware#0.4`
- Updated `friendsofphp/php-cs-fixer#2.0`

## [0.2.0] - 2016-11-22
### Changed
- Updated to `http-interop/http-middleware#0.3`

## [0.1.0] - 2016-10-02
First version

[#5]: https://github.com/middlewares/whoops/issues/5
[#7]: https://github.com/middlewares/whoops/issues/7
[#11]: https://github.com/middlewares/whoops/issues/11

[2.0.2]: https://github.com/middlewares/whoops/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/middlewares/whoops/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/middlewares/whoops/compare/v1.2.0...v2.0.0
[1.2.0]: https://github.com/middlewares/whoops/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/middlewares/whoops/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/middlewares/whoops/compare/v0.6.0...v1.0.0
[0.6.0]: https://github.com/middlewares/whoops/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/middlewares/whoops/compare/v0.4.1...v0.5.0
[0.4.1]: https://github.com/middlewares/whoops/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/middlewares/whoops/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/middlewares/whoops/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/whoops/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/middlewares/whoops/releases/tag/v0.1.0
