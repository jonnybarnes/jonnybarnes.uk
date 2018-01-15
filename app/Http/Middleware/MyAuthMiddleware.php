<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MyAuthMiddleware
{
    /**
     * Check the user is logged in.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('loggedin') !== true) {
            //they’re not logged in, so send them to login form
            return redirect()->route('login');
        }

        return $next($request);
    }
}
