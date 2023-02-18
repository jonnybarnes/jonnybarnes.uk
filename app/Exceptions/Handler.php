<?php

namespace App\Exceptions;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        NotFoundHttpException::class,
        ModelNotFoundException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function report(Throwable $e): void
    {
        parent::report($e);

        if (config('logging.slack') && $this->shouldReport($e)) {
            $guzzle = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $exceptionName = get_class($e) ?? 'Unknown Exception';
            $title = $exceptionName . ': ' . $e->getMessage();

            $guzzle->post(
                config('logging.slack'),
                [
                    'body' => json_encode([
                        'attachments' => [[
                            'fallback' => 'There was an exception.',
                            'pretext' => 'There was an exception.',
                            'color' => '#d00000',
                            'author_name' => app()->environment(),
                            'author_link' => config('app.url'),
                            'fields' => [[
                                'title' => $title,
                                'value' => request()->method() . ' ' . request()->fullUrl(),
                            ]],
                            'ts' => time(),
                        ]],
                    ]),
                ]
            );
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @return Response
     *
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
