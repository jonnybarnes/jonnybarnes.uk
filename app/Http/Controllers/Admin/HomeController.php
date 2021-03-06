<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the homepage of the admin CP.
     *
     * @return \Illuminate\View\View
     */
    public function welcome(): View
    {
        return view('admin.welcome', ['name' => config('admin.user')]);
    }
}
