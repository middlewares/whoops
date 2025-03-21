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
    private static function getContainer(): WhoopsHandlerContainer
    {
        return new class() extends WhoopsHandlerContainer {
            protected static function isCli(): bool
            {
                return false;
            }
        };
    }

    public function testJson(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'application/json');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testXml(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'text/xml');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->getHeaderLine('Content-Type'));
    }

    public function testPlain(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'text/plain');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
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
            (new Whoops())->handlerContainer(self::getContainer()),
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
            (new Whoops())->handlerContainer(self::getContainer()),
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
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }
}
