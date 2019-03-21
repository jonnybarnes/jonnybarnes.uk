<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('login');
    }

    /**
     * Log in a user, set a session variable, check credentials against
     * the .env file.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(): RedirectResponse
    {
        $credentials = request()->only('name', 'password');

        if (Auth::attempt($credentials, true)) {
            return redirect()->intended('/');
        }

        return redirect()->route('login');
    }
}
