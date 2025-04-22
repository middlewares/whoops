<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Middlewares\Utils\Dispatcher;
use Middlewares\Whoops;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run;

class WhoopsTest extends TestCase
{
    public function testError(): void
    {
        $response = Dispatcher::run([
            new Whoops(),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertNotFalse(strpos((string) $response->getBody(), 'Error Processing Request'));
    }

    public function testStandardError(): void
    {
        error_reporting(E_ALL);

        $response = Dispatcher::run([
            new Whoops(),
            function () {
                /** @phpstan-ignore variable.undefined */
                $a = $b; //undefined variable
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertNotFalse(strpos((string) $response->getBody(), 'Undefined variable'));
    }

    public function testPlainHandlerWithLoggerOnlyDisableChangesResponseBody(): void
    {
        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->sendHttpCode(false);

        $text = new PlainTextHandler(new NullLogger());
        $text->addTraceToOutput(true);
        $text->loggerOnly(false);

        $whoops->pushHandler($text);
        $whoops->register();

        $response = Dispatcher::run([
            (new Whoops($whoops)),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Stack trace:', (string)$response->getBody());
        $this->assertStringContainsString('Exception: Error Processing Request in file', (string)$response->getBody());
    }

    public function testPlainHandlerWithLoggerOnlyLeavesResponseUntouched(): void
    {
        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->sendHttpCode(false);

        $text = new PlainTextHandler(new NullLogger());
        $text->addTraceToOutput(true);
        $text->loggerOnly(true);

        $whoops->pushHandler($text);
        $whoops->register();

        $response = Dispatcher::run([
            (new Whoops($whoops)),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('', (string)$response->getBody());
    }

    public function testPlainHandlerWithLoggerOnlyAndPrettyResponseFactoryShowsPrettyResponse(): void
    {
        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->sendHttpCode(false);

        $text = new PlainTextHandler(new NullLogger());
        $text->addTraceToOutput(true);
        $text->loggerOnly(true);

        $whoops->pushHandler($text);
        $whoops->register();

        $prettyResponse = new class() implements ResponseFactoryInterface {
            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return new HtmlResponse('<strong>Sorry! Come back later</strong>', $code);
            }
        };

        $response = Dispatcher::run([
            (new Whoops($whoops, $prettyResponse)),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('<strong>Sorry! Come back later</strong>', (string)$response->getBody());
    }

    public function testWithoutError(): void
    {
        $response = Dispatcher::run([
            new Whoops(),
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
