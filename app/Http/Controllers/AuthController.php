<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function showLogin()
    {
        return view('login');
    }

    /**
     * Log in a user, set a sesion variable, check credentials against
     * the .env file.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login()
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
