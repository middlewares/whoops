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
use Whoops\Handler\PrettyPageHandler;
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

    public function testStandardErrorWithPrettyPageHandler(): void
    {
        error_reporting(E_ALL);

        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->sendHttpCode(false);

        $prettyPage = new PrettyPageHandler();
        $whoops->pushHandler($prettyPage);

        $whoops->register();

        $response = Dispatcher::run([
            new Whoops($whoops),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        // Whoops doesn't output anything on CLI
        $this->assertEquals('', (string) $response->getBody());
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
        $this->assertStringContainsString('Stack trace:', (string) $response->getBody());
        $this->assertStringContainsString('Exception: Error Processing Request in file', (string) $response->getBody());
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
        $this->assertEquals('', (string) $response->getBody());
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
        $this->assertEquals('<strong>Sorry! Come back later</strong>', (string) $response->getBody());
    }

    public function testPlainHandlerWithLoggerOnlyAndPrettyHandlerTakesThePrettyHandler(): void
    {
        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->sendHttpCode(false);

        $text = new PlainTextHandler(new NullLogger());
        $text->addTraceToOutput(true);
        $text->loggerOnly(true);
        $whoops->pushHandler($text);

        $prettyPage = new PrettyPageHandler();
        $whoops->pushHandler($prettyPage);

        $whoops->register();

        $response = Dispatcher::run([
            new Whoops($whoops),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        // for some reason PrettyPageHandler doesn't output anything in CLI
        $this->assertEquals('', (string) $response->getBody());
    }

    public function testWithLoggerOnlyItTakesTheFirstHandlerWithOutput(): void
    {
        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->sendHttpCode(false);

        $text = new PlainTextHandler(new NullLogger());
        $text->addTraceToOutput(true);
        // without output, ignored
        $text->loggerOnly(true);
        $whoops->pushHandler($text);

        $text = new PlainTextHandler(new NullLogger());
        $text->addTraceToOutput(true);
        // with output, taken first
        $text->loggerOnly(false);
        $whoops->pushHandler($text);

        // ignored
        $prettyPage = new PrettyPageHandler();
        $whoops->pushHandler($prettyPage);

        $whoops->register();

        $response = Dispatcher::run([
            new Whoops($whoops),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('Exception: Error Processing Request', (string) $response->getBody());
    }

    public function testItTakesTheFirstHandlerWithOutputWhenBothHaveOutput(): void
    {
        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->sendHttpCode(false);

        $prettyPage = new PrettyPageHandler();
        $whoops->pushHandler($prettyPage);

        $text = new PlainTextHandler(new NullLogger());
        $text->addTraceToOutput(true);
        $text->loggerOnly(false);
        $whoops->pushHandler($text);

        $whoops->register();

        $response = Dispatcher::run([
            new Whoops($whoops),
            function () {
                throw new Exception('Error Processing Request');
            },
        ]);

        $this->assertEquals(500, $response->getStatusCode());
        // the content type of pretty page handler is used
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        // the output of the plain text handler is used
        $this->assertStringStartsWith('Exception: Error Processing Request', (string) $response->getBody());

        // ... its interesting how Whoops works internally
    }

    public function testWithoutError(): void
    {
        $response = Dispatcher::run([
            new Whoops(),
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
