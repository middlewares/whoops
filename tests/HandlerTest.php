<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Eloquent\Phony\Phpunit\Phony;
use Middlewares\Utils\Dispatcher;
use Middlewares\Whoops;
use Middlewares\WhoopsHandlerContainer;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;

class HandlerTest extends TestCase
{
    private static function getContainer()
    {
        $container = Phony::partialMock(WhoopsHandlerContainer::class)->get();
        Phony::onStatic($container)->isCli->returns(false);

        return $container;
    }

    public function testJson()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'application/json');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testXml()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'text/xml');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->getHeaderLine('Content-Type'));
    }

    public function testPlain()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'text/plain');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }

    public function testHtml()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'text/html');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testDefault()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'foo/bar');

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testEmptyAccept()
    {
        $request = new ServerRequest();

        $response = Dispatcher::run([
            (new Whoops())->handlerContainer(self::getContainer()),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }
}
