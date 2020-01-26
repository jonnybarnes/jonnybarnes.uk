<?php

namespace App\Exceptions;

use App;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

/**
 * @codeCoverageIgnore
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $exception
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);

        $guzzle = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $guzzle->post(
            env('SLACK_WEBHOOK_URL'),
            [
                'body' => json_encode([
                    'attachments' => [[
                        'fallback' => 'There was an exception.',
                        'pretext' => 'There was an exception.',
                        'color' => '#d00000',
                        'author_name' => app()->environment(),
                        'author_link' => config('app.url'),
                        'fields' => [[
                            'title' => get_class($exception) ?? 'Unkown Exception',
                            'value' => $exception->getMessage() ?? '',
                        ]],
                        'ts' => time(),
                    ]],
                ]),
            ]
        );
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            Route::getRoutes()->match($request);
        }

        return parent::render($request, $exception);
    }
}
