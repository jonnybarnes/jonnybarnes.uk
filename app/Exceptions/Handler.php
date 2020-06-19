<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Throwable;

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
     * @param Throwable $throwable
     * @return void
     * @throws Exception
     */
    public function report(Throwable $throwable)
    {
        parent::report($throwable);

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
                            'title' => get_class($this) ?? 'Unknown Exception',
                            'value' => $throwable->getMessage() ?? '',
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
     * @param Request $request
     * @param Throwable $throwable
     * @return Response
     * @throws Throwable
     */
    public function render($request, Throwable $throwable)
    {
        if ($throwable instanceof TokenMismatchException) {
            Route::getRoutes()->match($request);
        }

        return parent::render($request, $throwable);
    }
}
