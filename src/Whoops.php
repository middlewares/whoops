<?php
declare(strict_types = 1);

namespace Middlewares;

use Middlewares\Utils\Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run;

class Whoops implements MiddlewareInterface
{
    /**
     * @var Run|null
     */
    private $whoops;

    /**
     * @var bool Whether to catch errors or not
     */
    private $catchErrors = true;

    /**
     * @var ContainerInterface|null
     */
    protected $handlerContainer;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * Set the whoops instance.
     */
    public function __construct(
        ?Run $whoops = null,
        ?ResponseFactoryInterface $responseFactory = null
    ) {
        $this->whoops = $whoops;
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
    }

    /**
     * Whether catch errors or not.
     */
    public function catchErrors(bool $catchErrors = true): self
    {
        $this->catchErrors = $catchErrors;

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
        $whoops = $this->whoops ?: $this->createWhoopsInstance($request);

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

            register_shutdown_function($shutdown);
        }

        try {
            $response = $handler->handle($request);
        } catch (Throwable $exception) {
            $response = $this->responseFactory->createResponse(500);

            if (self::shouldMutateResponse($whoops)) {
                $response->getBody()->write($whoops->$method($exception));
                $response = self::updateResponseContentType($response, $whoops);
            }
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
     * Creates a Whoops instance in case one was not provided.
     */
    protected function createWhoopsInstance(ServerRequestInterface $request): Run
    {
        $whoops = new Run();
        $container = $this->handlerContainer ?: new WhoopsHandlerContainer();
        $handler = $container->get($request->getHeaderLine('Accept'));
        $whoops->appendHandler($handler);

        return $whoops;
    }

    private static function shouldMutateResponse(Run $whoops): bool
    {
        if (1 !== count($whoops->getHandlers())) {
            return false;
        }

        $handler = current($whoops->getHandlers());

        return !($handler instanceof PlainTextHandler && $handler->loggerOnly());
    }

    /**
     * Updates Response's content type in order to match Handler's content type.
     */
    private static function updateResponseContentType(ResponseInterface $response, Run $whoops): ResponseInterface
    {
        if (1 !== count($whoops->getHandlers())) {
            return $response;
        }

        $handler = current($whoops->getHandlers());

        if (method_exists($handler, 'contentType')) {
            return $response->withHeader('Content-Type', $handler->contentType());
        }

        return $response;
    }
}
