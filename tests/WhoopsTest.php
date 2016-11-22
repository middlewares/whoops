<?php

namespace Middlewares\Tests;

use Middlewares\Whoops;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\CallableMiddleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

class WhoopsTest extends \PHPUnit_Framework_TestCase
{
    public function testError()
    {
        $response = (new Dispatcher([
            new Whoops(),
            new CallableMiddleware(function () {
                throw new \Exception('Error Processing Request');
            }),
        ]))->dispatch(new ServerRequest());

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertNotFalse(strpos($response->getBody(), 'Error Processing Request'));
    }

    public function testNotError()
    {
        $response = (new Dispatcher([
            new Whoops(),
            new CallableMiddleware(function () {
                return new Response();
            }),
        ]))->dispatch(new ServerRequest());

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
