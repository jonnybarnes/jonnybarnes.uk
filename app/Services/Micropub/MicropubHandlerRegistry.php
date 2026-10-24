<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Exceptions\MicropubHandlerException;
use App\Services\Micropub\Handlers\MicropubHandlerInterface;

/**
 * Maps Micropub post types to their handler instances.
 *
 * MicropubRequest normalises every incoming request and resolves it to a type
 * string ("entry", "card", "update"). The controller asks the registry for the
 * right handler, then asks the handler which data class to build, and finally
 * calls handle() with that data object.
 *
 * Flow:
 *   MicropubRequest (normalise) → MicropubController
 *     → MicropubHandlerRegistry::getHandler($type)
 *     → $handler->dataClass()::fromArray($rawData)
 *     → $handler->handle($dataObject)
 *
 * Handlers are registered in MicropubServiceProvider. To support a new
 * Micropub post type, create a handler in App\Services\Micropub\Handlers, a
 * matching data class in App\Services\Micropub\Data, and register the handler
 * here with its type string.
 */
class MicropubHandlerRegistry
{
    /**
     * @var MicropubHandlerInterface[]
     */
    protected array $handlers = [];

    /**
     * Register a handler for a given Micropub type string.
     */
    public function register(string $type, MicropubHandlerInterface $handler): self
    {
        $this->handlers[$type] = $handler;

        return $this;
    }

    /**
     * Retrieve the handler for a given type, or throw if none is registered.
     *
     * @throws MicropubHandlerException
     */
    public function getHandler(string $type): MicropubHandlerInterface
    {
        if (! isset($this->handlers[$type])) {
            throw new MicropubHandlerException("Unsupported Micropub request type '{$type}'");
        }

        return $this->handlers[$type];
    }
}
