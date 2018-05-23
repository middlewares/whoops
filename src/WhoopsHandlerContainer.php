<?php
declare(strict_types = 1);

namespace Middlewares;

use Psr\Container\ContainerInterface;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;

/**
 * Resolve a callable using a container.
 */
class WhoopsHandlerContainer implements ContainerInterface
{
    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return HandlerInterface
     */
    public function get($id)
    {
        $format = self::getPreferredFormat($id);

        return $this->$format();
    }

    protected function json(): HandlerInterface
    {
        $handler = new JsonResponseHandler();
        $handler->addTraceToOutput(true);

        return $handler;
    }

    protected function xml(): HandlerInterface
    {
        $handler = new XmlResponseHandler();
        $handler->addTraceToOutput(true);

        return $handler;
    }

    protected function html(): HandlerInterface
    {
        return new PrettyPageHandler();
    }

    protected function plain(): HandlerInterface
    {
        $handler = new PlainTextHandler();
        $handler->addTraceToOutput(true);

        return $handler;
    }

    protected function unknown(): HandlerInterface
    {
        return $this->html();
    }

    /**
     * Returns the preferred format used by whoops.
     */
    protected static function getPreferredFormat(string $accept): string
    {
        if (static::isCli()) {
            return 'plain';
        }

        $formats = [
            'json' => ['application/json'],
            'html' => ['text/html'],
            'xml' => ['text/xml'],
            'plain' => ['text/plain', 'text/css', 'text/javascript'],
        ];

        foreach ($formats as $format => $mimes) {
            foreach ($mimes as $mime) {
                if (stripos($accept, $mime) !== false) {
                    return $format;
                }
            }
        }

        return 'unknown';
    }

    protected static function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }
}
