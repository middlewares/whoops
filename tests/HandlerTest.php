<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\Utils\Dispatcher;
use Middlewares\Whoops;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\XmlResponseHandler;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\ServerRequest;
use function uopz_set_return;
use function uopz_unset_return;

class HandlerTest extends TestCase
{

    public function setUp()
    {
        uopz_set_return("php_sapi_name", "phpunit");
    }


    public function tearDown()
    {
        uopz_unset_return("php_sapi_name");
    }


    public function testJson()
    {
        $request = (new ServerRequest)->withHeader('Accept', 'application/json');

        $response = Dispatcher::run([
            new Whoops(),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }


    public function testXml()
    {
        $request = (new ServerRequest)->withHeader('Accept', 'text/xml');

        $response = Dispatcher::run([
            new Whoops(),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->getHeaderLine('Content-Type'));
    }


    public function testPlain()
    {
        $request = (new ServerRequest)->withHeader('Accept', 'text/plain');

        $response = Dispatcher::run([
            new Whoops(),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }


    public function testDefault()
    {
        $request = (new ServerRequest)->withHeader('Accept', 'text/html');

        $response = Dispatcher::run([
            new Whoops(),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ], $request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }
}
