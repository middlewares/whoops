<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\XmlResponseHandler;

class Whoops implements ServerMiddlewareInterface
{
    /**
     * @var Run|null
     */
    private $whoops;

    /**
     * @var bool Whether catch errors or not
     */
    private $catchErrors = true;

    /**
     * Set the whoops instance.
     *
     * @param Run|null $whoops
     */
    public function __construct(Run $whoops = null)
    {
        $this->whoops = $whoops;
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
        $whoops = $this->whoops ?: self::getWhoopsInstance($request);

        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->sendHttpCode(false);

        //Catch errors means register whoops globally
        if ($this->catchErrors) {
            $whoops->register();
        }

        try {
            $response = $delegate->process($request);
        } catch (\Throwable $exception) {
            $response = Utils\Factory::createResponse(500);
            $response->getBody()->write($whoops->$method($exception));
        } catch (\Exception $exception) {
            $response = Utils\Factory::createResponse(500);
            $response->getBody()->write($whoops->$method($exception));
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
    private static function getWhoopsInstance(ServerRequestInterface $request)
    {
        $whoops = new Run();

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
}
