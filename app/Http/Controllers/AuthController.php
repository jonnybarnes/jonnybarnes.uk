<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return View|RedirectResponse
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
     * @return View|RedirectResponse
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
     * @return RedirectResponse;
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect('/');
    }
}
