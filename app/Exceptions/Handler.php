<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exc
     * @return void
     */
    public function report(Exception $exc)
    {
        parent::report($exc);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exc
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exc)
    {
        if (config('app.debug')) {
            return $this->renderExceptionWithWhoops($exc);
        }

        if ($exc instanceof ModelNotFoundException) {
            $exc = new NotFoundHttpException($exc->getMessage(), $exc);
        }

        if ($exc instanceof TokenMismatchException) {
            return redirect()->back()
                ->withInput($request->except('password', '_token'))
                ->withErrors('Validation Token has expired. Please try again', 'csrf');
        }

        return parent::render($request, $exc);
    }

    /**
     * Render an exception using Whoops.
     *
     * @param  \Exception $exc
     * @return \Illuminate\Http\Response
     */
    protected function renderExceptionWithWhoops(Exception $exc)
    {
        $whoops = new \Whoops\Run;
        $handler = new \Whoops\Handler\PrettyPageHandler();
        $handler->setEditor(function ($file, $line) {
            return "atom://open?file=$file&line=$line";
        });
        $whoops->pushHandler($handler);

        return new \Illuminate\Http\Response(
            $whoops->handleException($exc),
            $exc->getStatusCode(),
            $exc->getHeaders()
        );
    }
}
