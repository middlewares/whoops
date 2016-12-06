<?php

namespace Middlewares\Tests;

use Middlewares\Whoops;
use Middlewares\Utils\Dispatcher;

class WhoopsTest extends \PHPUnit_Framework_TestCase
{
    public function testError()
    {
        $response = Dispatcher::run([
            new Whoops(),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ]);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertNotFalse(strpos($response->getBody(), 'Error Processing Request'));
    }

    public function testNotError()
    {
        $response = Dispatcher::run([
            new Whoops(),
        ]);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
