<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Eloquent\Phony\Phpunit\Phony;
use Middlewares\Utils\Dispatcher;
use Middlewares\Whoops;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\XmlResponseHandler;
use Zend\Diactoros\ServerRequest;

class HandlerTest extends TestCase
{
    public function testJson()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'application/json');

        $whoops = Phony::partialMock(Whoops::class)->get();
        Phony::onStatic($whoops)->isCli->returns(false);

        $response = Dispatcher::run([
            $whoops,
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

        $whoops = Phony::partialMock(Whoops::class)->get();
        Phony::onStatic($whoops)->isCli->returns(false);

        $response = Dispatcher::run([
            $whoops,
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

        $whoops = Phony::partialMock(Whoops::class)->get();
        Phony::onStatic($whoops)->isCli->returns(false);

        $response = Dispatcher::run([
            $whoops,
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }

    public function testDefault()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'text/html');

        $whoops = Phony::partialMock(Whoops::class)->get();
        Phony::onStatic($whoops)->isCli->returns(false);

        $response = Dispatcher::run([
            $whoops,
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testCustom()
    {
        $request = (new ServerRequest())->withHeader('Accept', 'text/html');

        $whoops = Phony::partialMock(Whoops::class)->get();
        Phony::onStatic($whoops)->isCli->returns(false);

        $response = Dispatcher::run([
            $whoops->defaultHandler(new XmlResponseHandler()),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->getHeaderLine('Content-Type'));
    }
}
