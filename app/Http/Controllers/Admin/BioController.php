<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class BioController extends Controller
{
    public function show(): View
    {
        $bio = Bio::first();

        return view('admin.bio.show', [
            'bioEntry' => $bio,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $bio = Bio::firstOrNew();
        $bio->content = $request->input('content');
        $bio->save();

        return redirect()->route('admin.bio.show');
    }
}
