<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyndicationTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyndicationTargetsController extends Controller
{
    /**
     * Show a list of known syndication targets.
     */
    public function index(): View
    {
        $targets = SyndicationTarget::all();

        return view('admin.syndication.index', compact('targets'));
    }

    /**
     * Show form to add a syndication target.
     */
    public function create(): View
    {
        return view('admin.syndication.create');
    }

    /**
     * Process the request to adda new syndication target.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'uid' => 'required|string',
            'name' => 'required|string',
            'service_name' => 'nullable|string',
            'service_url' => 'nullable|string',
            'service_photo' => 'nullable|string',
            'user_name' => 'nullable|string',
            'user_url' => 'nullable|string',
            'user_photo' => 'nullable|string',
        ]);

        SyndicationTarget::create($validated);

        return redirect('/admin/syndication');
    }

    /**
     * Show a form to edit a syndication target.
     */
    public function edit(SyndicationTarget $syndicationTarget): View
    {
        return view('admin.syndication.edit', [
            'syndication_target' => $syndicationTarget,
        ]);
    }

    /**
     * Process the request to edit a client name.
     */
    public function update(Request $request, SyndicationTarget $syndicationTarget): RedirectResponse
    {
        $validated = $request->validate([
            'uid' => 'required|string',
            'name' => 'required|string',
            'service_name' => 'nullable|string',
            'service_url' => 'nullable|string',
            'service_photo' => 'nullable|string',
            'user_name' => 'nullable|string',
            'user_url' => 'nullable|string',
            'user_photo' => 'nullable|string',
        ]);

        $syndicationTarget->update($validated);

        return redirect('/admin/syndication');
    }

    /**
     * Process a request to delete a client.
     */
    public function destroy(SyndicationTarget $syndicationTarget): RedirectResponse
    {
        $syndicationTarget->delete();

        return redirect('/admin/syndication');
    }
}
