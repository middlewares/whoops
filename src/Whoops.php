<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Whoops\Run;
use Whoops\Util\SystemFacade;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\XmlResponseHandler;

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
     * @var bool Whether catch errors or not
     */
    private $catchErrors = true;

    /**
     * Set the whoops instance.
     *
     * @param Run|null $whoops
     * @param SystemFacade|null $systemFacade
     */
    public function __construct(Run $whoops = null, SystemFacade $system = null)
    {
        $this->whoops = $whoops;
        $this->system = $system;
    }

    /**
     * Whether catch errors or not.
     *
     * @param bool $catchErrors
     *
     * @return self
     */
    public function catchErrors($catchErrors = true)
    {
        $this->catchErrors = (bool) $catchErrors;

        return $this;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
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
            $response = $delegate->process($request);
        } catch (\Throwable $exception) {
            $response = Utils\Factory::createResponse(500);
            $response->getBody()->write($whoops->$method($exception));
            $response = self::updateResponseContentType($response, $whoops);
        } catch (\Exception $exception) {
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
     *
     * @param ServerRequestInterface $request
     *
     * @return Run
     */
    private function getWhoopsInstance(ServerRequestInterface $request)
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
                $handler = new PrettyPageHandler();
                break;
        }

        $whoops->pushHandler($handler);

        return $whoops;
    }

    /**
     * Returns the preferred format used by whoops.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    private static function getPreferredFormat($request)
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
     *
     * @param ResponseInterface $response
     * @param Run $whoops
     *
     * @return ResponseInterface
     */
    private static function updateResponseContentType(ResponseInterface $response, Run $whoops)
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
