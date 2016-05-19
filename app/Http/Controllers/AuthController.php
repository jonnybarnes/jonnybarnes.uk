<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Log in a user, set a sesion variable, check credentials against
     * the .env file.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function login(Request $request)
    {
        if ($request->input('username') === env('ADMIN_USER')
            &&
            $request->input('password') === env('ADMIN_PASS')
        ) {
            session(['loggedin' => true]);

            return redirect()->intended('admin');
        }

        return redirect()->route('login');
    }
}
