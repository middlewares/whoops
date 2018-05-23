<?php
declare(strict_types = 1);

namespace Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;
use Whoops\Util\SystemFacade;

class Whoops implements MiddlewareInterface
{
    /**
     * @var Run|null
     */
    private $whoops;

    /**
     * @var SystemFacade|null
     */
    private $system;

    /**
     * @var HandlerInterface|null
     */
    private $handler;

    /**
     * @var bool Whether catch errors or not
     */
    private $catchErrors = true;

    /**
     * Set the whoops instance.
     */
    public function __construct(Run $whoops = null, SystemFacade $system = null)
    {
        $this->whoops = $whoops;
        $this->system = $system;
    }

    /**
     * Whether catch errors or not.
     */
    public function catchErrors(bool $catchErrors = true): self
    {
        $this->catchErrors = (bool) $catchErrors;

        return $this;
    }

    /**
     * Set the default handler to use (instead of the standard PrettyPrintHandler).
     *
     * @param HandlerInterface $handler The default handler to use
     *
     * @return $this
     */
    public function defaultHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        ob_start();
        $level = ob_get_level();

        $method = Run::EXCEPTION_HANDLER;
        $whoops = $this->whoops ?: $this->getWhoopsInstance($request);

        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->sendHttpCode(false);

        //Catch errors means register whoops globally
        if ($this->catchErrors) {
            $whoops->register();

            $shutdown = function () use ($whoops) {
                $whoops->allowQuit(true);
                $whoops->writeToOutput(true);
                $whoops->sendHttpCode(true);

                $method = Run::SHUTDOWN_HANDLER;
                $whoops->$method();
            };

            if ($this->system) {
                $this->system->registerShutdownFunction($shutdown);
            } else {
                register_shutdown_function($shutdown);
            }
        }

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $exception) {
            $response = Utils\Factory::createResponse(500);
            $response->getBody()->write($whoops->$method($exception));
            $response = self::updateResponseContentType($response, $whoops);
        } finally {
            while (ob_get_level() >= $level) {
                ob_end_clean();
            }
        }

        if ($this->catchErrors) {
            $whoops->unregister();
        }

        return $response;
    }

    /**
     * Returns the whoops instance or create one.
     */
    private function getWhoopsInstance(ServerRequestInterface $request): Run
    {
        if (!$this->system) {
            $this->system = new SystemFacade();
        }

        $whoops = new Run($this->system);

        switch (self::getPreferredFormat($request)) {
            case 'json':
                $handler = new JsonResponseHandler();
                $handler->addTraceToOutput(true);
                break;
            case 'xml':
                $handler = new XmlResponseHandler();
                $handler->addTraceToOutput(true);
                break;
            case 'plain':
                $handler = new PlainTextHandler();
                $handler->addTraceToOutput(true);
                break;
            default:
                $handler = $this->handler ?: new PrettyPageHandler();
                break;
        }

        $whoops->pushHandler($handler);

        return $whoops;
    }

    /**
     * Returns the preferred format used by whoops.
     *
     * @return string|null
     */
    private static function getPreferredFormat(ServerRequestInterface $request)
    {
        if (php_sapi_name() === 'cli') {
            return 'plain';
        }

        $formats = [
            'json' => ['application/json'],
            'html' => ['text/html'],
            'xml' => ['text/xml'],
            'plain' => ['text/plain', 'text/css', 'text/javascript'],
        ];

        $accept = $request->getHeaderLine('Accept');

        foreach ($formats as $format => $mimes) {
            foreach ($mimes as $mime) {
                if (stripos($accept, $mime) !== false) {
                    return $format;
                }
            }
        }
    }

    /**
     * Returns the content-type for the whoops instance
     */
    private static function updateResponseContentType(ResponseInterface $response, Run $whoops): ResponseInterface
    {
        if (1 !== count($whoops->getHandlers())) {
            return $response;
        }

        $handler = current($whoops->getHandlers());

        if ($handler instanceof PrettyPageHandler) {
            return $response->withHeader('Content-Type', 'text/html');
        }

        if ($handler instanceof JsonResponseHandler) {
            return $response->withHeader('Content-Type', 'application/json');
        }

        if ($handler instanceof XmlResponseHandler) {
            return $response->withHeader('Content-Type', 'text/xml');
        }

        if ($handler instanceof PlainTextHandler) {
            return $response->withHeader('Content-Type', 'text/plain');
        }

        return $response;
    }
}
