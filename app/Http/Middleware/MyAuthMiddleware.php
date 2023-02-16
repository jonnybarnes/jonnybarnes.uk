<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyAuthMiddleware
{
    /**
     * Check the user is logged in.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check($request->user()) == false) {
            //they’re not logged in, so send them to login form
            return redirect()->route('login');
        }

        return $next($request);
    }
}
