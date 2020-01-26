<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Show the form to logout a user.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLogout()
    {
        if (Auth::check() === false) {
            // The user is not logged in, just redirect them home
            return redirect('/');
        }

        return view('logout');
    }

    /**
     * Log the user out from their current session.
     *
     * @return \Illuminate\Http\RedirectResponse;
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect('/');
    }
}
