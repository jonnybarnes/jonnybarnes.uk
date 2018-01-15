<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLogin(): View
    {
        return view('login');
    }

    /**
     * Log in a user, set a sesion variable, check credentials against
     * the .env file.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(): RedirectResponse
    {
        if (request()->input('username') === config('admin.user')
            &&
            request()->input('password') === config('admin.pass')
        ) {
            session(['loggedin' => true]);

            return redirect()->intended('admin');
        }

        return redirect()->route('login');
    }
}
