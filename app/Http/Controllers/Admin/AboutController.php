<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\About;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function show(): View
    {
        $about = About::first();

        return view('admin.about.show', [
            'aboutEntry' => $about,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $about = About::firstOrNew();
        $about->content = $request->input('content');
        $about->save();

        return redirect()->route('admin.about.show');
    }
}
