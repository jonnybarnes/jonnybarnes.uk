<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    /**
     * Show the homepage of the admin CP.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function welcome()
    {
        return view('admin.welcome', ['name' => config('admin.user')]);
    }
}
