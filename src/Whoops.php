<?php
declare(strict_types = 1);

namespace Middlewares;

use Middlewares\Utils\Traits\HasResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;
use Whoops\Util\SystemFacade;

class Whoops implements MiddlewareInterface
{
    use HasResponseFactory;

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
     * @var ContainerInterface|null
     */
    private $handlerContainer;

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
     * Set the PSR-11 container to create the error handler using the Accept header
     */
    public function handlerContainer(ContainerInterface $handlerContainer): self
    {
        $this->handlerContainer = $handlerContainer;

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
            $response = $this->createResponse(500);
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
        $container = $this->handlerContainer ?: new WhoopsHandlerContainer();
        $handler = $container->get($request->getHeaderLine('Accept'));
        $whoops->pushHandler($handler);

        return $whoops;
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
