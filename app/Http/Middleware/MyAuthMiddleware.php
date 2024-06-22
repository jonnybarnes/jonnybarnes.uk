<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MyAuthMiddleware
{
    /**
     * Check the user is logged in.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() === false) {
            // they’re not logged in, so send them to login form
            redirect()->setIntendedUrl($request->fullUrl());

            return redirect()->route('login');
        }

        return $next($request);
    }
}
