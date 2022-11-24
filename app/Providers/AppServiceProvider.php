<?php

namespace App\Providers;

use App\Models\Note;
use App\Observers\NoteObserver;
use Codebird\Codebird;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Dusk\DuskServiceProvider;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Note::observe(NoteObserver::class);

        // Request AS macro
        Request::macro('wantsActivityStream', function () {
            return Str::contains(mb_strtolower($this->header('Accept')), 'application/activity+json');
        });

        // configure Intervention/Image
        $this->app->bind('Intervention\Image\ImageManager', function () {
            return new \Intervention\Image\ImageManager(['driver' => config('image.driver')]);
        });

        // Bind the Codebird client
        // Codebird gets mocked in tests
        // @codeCoverageIgnoreStart
        $this->app->bind('Codebird\Codebird', function () {
            Codebird::setConsumerKey(
                env('TWITTER_CONSUMER_KEY'),
                env('TWITTER_CONSUMER_SECRET')
            );

            $cb = Codebird::getInstance();

            $cb->setToken(
                env('TWITTER_ACCESS_TOKEN'),
                env('TWITTER_ACCESS_TOKEN_SECRET')
            );

            return $cb;
        });
        // @codeCoverageIgnoreEnd

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

            $config = Configuration::forSymmetricSigner(new Sha256(), $key);

            $config->setValidationConstraints(new SignedWith(new Sha256(), $key));

            return $config;
        });

        // Configure HtmlSanitizer
        $this->app->bind(HtmlSanitizer::class, function () {
            return new HtmlSanitizer(
                (new HtmlSanitizerConfig())
                    ->allowSafeElements()
                    ->forceAttribute('a', 'rel', 'noopener nofollow')
            );
        });

        // Turn on Eloquent strict mode when developing
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing')) {
            $this->app->register(DuskServiceProvider::class);
        }
    }
}
