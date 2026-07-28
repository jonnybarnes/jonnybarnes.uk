<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // configure Intervention/Image
        $this->app->bind('Intervention\Image\ImageManager', function () {
            return ImageManager::withDriver(config('image.driver'));
        });

        /**
         * Paginate a standard Laravel Collection.
         *
         * @param  int  $perPage
         * @param  int  $total
         * @param  int  $page
         * @param  string  $pageName
         * @return array
         */
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });

        // Configure JWT builder
        $this->app->bind('Lcobucci\JWT\Configuration', function () {
            $key = InMemory::plainText(config('app.key'));

            $config = Configuration::forSymmetricSigner(new Sha256, $key);

            $config->setValidationConstraints(new SignedWith(new Sha256, $key));

            return $config;
        });

        // Configure HtmlSanitizer
        $this->app->bind(HtmlSanitizer::class, function () {
            return new HtmlSanitizer(
                (new HtmlSanitizerConfig)
                    ->allowSafeElements()
                    ->forceAttribute('a', 'rel', 'noopener nofollow')
            );
        });

        // Turn on Eloquent strict mode when developing
        Model::shouldBeStrict(! $this->app->isProduction());

        // Force HTTPS URL generation in production
        URL::forceHttps($this->app->isProduction());
    }
}
