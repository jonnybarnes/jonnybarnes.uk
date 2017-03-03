<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function welcome()
    {
        return view('welcome', ['name' => config('admin.user')]);
    }
}
