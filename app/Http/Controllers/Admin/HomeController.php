<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function welcome()
    {
        return view('admin.welcome', ['name' => config('admin.user')]);
    }
}
