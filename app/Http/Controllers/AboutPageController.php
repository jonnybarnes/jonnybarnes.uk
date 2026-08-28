<?php

namespace App\Http\Controllers;

use App\Models\About;
use Illuminate\View\View;

class AboutPageController extends Controller
{
    public function show(): View
    {
        return view('about', [
            'about' => About::first()?->content,
        ]);
    }
}
