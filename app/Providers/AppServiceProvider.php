<?php

namespace App\Providers;

use App\Models\Note;
use App\Observers\NoteObserver;
use Codebird\Codebird;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
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
        Note::observe(NoteObserver::class);

        // configure Intervention/Image
        $this->app->bind('Intervention\Image\ImageManager', function () {
            return \Intervention\Image\ImageManager::withDriver(config('image.driver'));
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

        // Configure Guzzle
        $this->app->bind('RetryGuzzle', function () {
            $handlerStack = \GuzzleHttp\HandlerStack::create();
            $handlerStack->push(Middleware::retry(
                function ($retries, $request, $response, $exception) {
                    // Limit the number of retries to 5
                    if ($retries >= 5) {
                        return false;
                    }

                    // Retry connection exceptions
                    if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                        return true;
                    }

                    // Retry on server errors
                    if ($response && $response->getStatusCode() >= 500) {
                        return true;
                    }

                    // Finally for CloudConvert, retry if status is not final
                    return json_decode($response, false, 512, JSON_THROW_ON_ERROR)->data->status !== 'finished';
                },
                function () {
                    // Retry after 1 second
                    return 1000;
                }
            ));

            return new Client(['handler' => $handlerStack]);
        });

        // Turn on Eloquent strict mode when developing
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
