<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaticRoutesController extends Controller
{
    public function projects()
    {
        return view('projects');
    }

    public function colophon()
    {
        return view('colophon');
    }
}
