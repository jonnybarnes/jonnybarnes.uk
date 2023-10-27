<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('login');
    }

    /**
     * Log in a user, set a session variable, check credentials against the `.env` file.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->only('name', 'password');

        if (Auth::attempt($credentials, true)) {
            return redirect()->intended('/admin');
        }

        return redirect()->route('login');
    }

    /**
     * Show the form to allow a user to log-out.
     */
    public function showLogout(): View|RedirectResponse
    {
        if (Auth::check() === false) {
            // The user is not logged in, just redirect them home
            return redirect('/');
        }

        return view('logout');
    }

    /**
     * Log the user out from their current session.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect('/');
    }
}
