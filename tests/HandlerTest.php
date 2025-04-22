<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Exception;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Middlewares\Whoops;
use Middlewares\WhoopsHandlerContainer;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    private static function getHttpContainer(): WhoopsHandlerContainer
    {
        return new class() extends WhoopsHandlerContainer {
            protected static function isCli(): bool
            {
                return false;
            }
        };
    }

    private static function getCliContainer(): WhoopsHandlerContainer
    {
        return new class() extends WhoopsHandlerContainer {
            protected static function isCli(): bool
            {
                return true;
            }
        };
    }

    public function testJson(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'application/json');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getHttpContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function dataProviderForXml(): array
    {
        return [
            ['text/xml'],
            ['application/xml'],
        ];
    }

    /**
     * @dataProvider dataProviderForXml
     */
    public function testXml(string $accept): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', $accept);

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getHttpContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/xml', $response->getHeaderLine('Content-Type'));
    }

    public function dataProviderForPlain(): array
    {
        return [
            ['text/plain'],
            ['text/css'],
            ['text/javascript; charset=utf-8'],
            ['application/javascript'],
        ];
    }

    /**
     * @dataProvider dataProviderForPlain
     */
    public function testPlain(string $accept): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', $accept);

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getHttpContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }

    public function testHtml(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'text/html');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getHttpContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testDefault(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'foo/bar');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getHttpContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testEmptyAccept(): void
    {
        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getHttpContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testCli(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'foo/bar');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getCliContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }
}
