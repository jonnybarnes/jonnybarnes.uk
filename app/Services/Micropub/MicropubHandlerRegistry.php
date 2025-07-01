<?php

declare(strict_types=1);

namespace App\Services\Micropub;

use App\Exceptions\MicropubHandlerException;

class MicropubHandlerRegistry
{
    /**
     * @var MicropubHandlerInterface[]
     */
    protected array $handlers = [];

    public function register(string $type, MicropubHandlerInterface $handler): self
    {
        $this->handlers[$type] = $handler;

        return $this;
    }

    /**
     * @throws MicropubHandlerException
     */
    public function getHandler(string $type): MicropubHandlerInterface
    {
        if (! isset($this->handlers[$type])) {
            throw new MicropubHandlerException("No handler registered for '{$type}'");
        }

        return $this->handlers[$type];
    }
}
