<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function welcome()
    {
        return view('admin.welcome', ['name' => config('admin.user')]);
    }
}
