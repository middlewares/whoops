<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\Utils\Dispatcher;
use Middlewares\Whoops;
use PHPUnit\Framework\TestCase;

class WhoopsTest extends TestCase
{
    public function testError()
    {
        $response = Dispatcher::run([
            new Whoops(),
            function () {
                throw new \Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertNotFalse(strpos((string) $response->getBody(), 'Error Processing Request'));
    }

    public function testStandardError()
    {
        $response = Dispatcher::run([
            new Whoops(),
            function () {
                $a = $b; //undefined variable
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertNotFalse(strpos((string) $response->getBody(), 'Undefined variable: b'));
    }

    public function testNotError()
    {
        $response = Dispatcher::run([
            new Whoops(),
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
