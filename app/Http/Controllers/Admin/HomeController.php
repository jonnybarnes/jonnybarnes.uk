<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;

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
