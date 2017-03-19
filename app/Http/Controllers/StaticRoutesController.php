<?php

namespace App\Http\Controllers;

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
