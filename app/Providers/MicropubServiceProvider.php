<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Micropub\CardHandler;
use App\Services\Micropub\EntryHandler;
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

            return $registry;
        });
    }
}
