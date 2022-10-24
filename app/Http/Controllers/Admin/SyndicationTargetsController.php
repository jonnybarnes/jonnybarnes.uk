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
     *
     * @return View
     */
    public function index(): View
    {
        $targets = SyndicationTarget::all();

        return view('admin.syndication.index', compact('targets'));
    }

    /**
     * Show form to add a syndication target.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.syndication.create');
    }

    /**
     * Process the request to adda new syndication target.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'uid' => 'required|string',
            'name' => 'required|string',
        ]);

        SyndicationTarget::create($validated);

        return redirect('/admin/syndication');
    }

    /**
     * Show a form to edit a syndication target.
     *
     * @param  SyndicationTarget  $syndicationTarget
     * @return View
     */
    public function edit(SyndicationTarget $syndicationTarget): View
    {
        return view('admin.syndication.edit', [
            'syndication_target' => $syndicationTarget,
        ]);
    }

    /**
     * Process the request to edit a client name.
     *
     * @param  Request  $request
     * @param  SyndicationTarget  $syndicationTarget
     * @return RedirectResponse
     */
    public function update(Request $request, SyndicationTarget $syndicationTarget): RedirectResponse
    {
        $validated = $request->validate([
            'uid' => 'required|string',
            'name' => 'required|string',
        ]);

        $syndicationTarget->update($validated);

        return redirect('/admin/syndication');
    }

    /**
     * Process a request to delete a client.
     *
     * @param  SyndicationTarget  $syndicationTarget
     * @return RedirectResponse
     */
    public function destroy(SyndicationTarget $syndicationTarget): RedirectResponse
    {
        $syndicationTarget->delete();

        return redirect('/admin/syndication');
    }
}
