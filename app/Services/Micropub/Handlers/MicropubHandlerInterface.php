<?php

declare(strict_types=1);

namespace App\Services\Micropub\Handlers;

use App\Services\Micropub\Data\MicropubData;

/**
 * Contract for all Micropub request handlers.
 *
 * The Micropub endpoint receives different types of request — creating an
 * h-entry (note, article, bookmark, like), creating an h-card (place), or
 * updating an existing post. Each type is handled by its own class that
 * implements this interface.
 *
 * Each handler declares the typed data object it needs via dataClass(). The
 * controller calls dataClass()::fromArray() to build the right object before
 * passing it to handle(), so the handler always receives a concrete typed
 * object rather than a raw array.
 *
 * Handlers live in App\Services\Micropub\Handlers.
 * Their corresponding data objects live in App\Services\Micropub\Data.
 * Handlers are registered in MicropubServiceProvider and looked up by type
 * string (e.g. "entry", "card", "update") via MicropubHandlerRegistry.
 */
interface MicropubHandlerInterface
{
    /**
     * Return the fully-qualified class name of the data object this handler
     * expects. The controller will call fromArray() on this class to build the
     * object from the normalised request data before calling handle().
     *
     * @return class-string<MicropubData>
     */
    public function dataClass(): string;

    /**
     * Process the request and return a result array with at minimum a
     * 'response' key ('created' or 'updated') and a 'url' key pointing to
     * the affected resource.
     */
    public function handle(MicropubData $data): array;
}
