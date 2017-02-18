<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Here we have the logic for the admin cp
    |
     */

    /**
     * Set variables.
     *
     * @var string
     */
    public function __construct()
    {
        $this->username = config('admin.user');
    }

    /**
     * Show the main admin CP page.
     *
     * @return \Illuminate\View\Factory view
     */
    public function showWelcome()
    {
        return view('admin.welcome', ['name' => $this->username]);
    }
}
