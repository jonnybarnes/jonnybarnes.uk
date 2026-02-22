<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Micropub\Handlers\CardHandler;
use App\Services\Micropub\Handlers\EntryHandler;
use App\Services\Micropub\Handlers\UpdateHandler;
use App\Services\Micropub\MicropubHandlerRegistry;
use Illuminate\Support\ServiceProvider;

class MicropubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MicropubHandlerRegistry::class, function () {
            $registry = new MicropubHandlerRegistry;

            // Register handlers
            $registry->register('card', new CardHandler);
            $registry->register('entry', new EntryHandler);
            $registry->register('update', new UpdateHandler);

            return $registry;
        });
    }
}
