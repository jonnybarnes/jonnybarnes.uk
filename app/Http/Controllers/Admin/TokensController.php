<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MicropubToken;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TokensController extends Controller
{
    /**
     * Show a list of issued Micropub tokens.
     */
    public function index(): View
    {
        $tokens = MicropubToken::latest()->get();

        return view('admin.tokens.index', compact('tokens'));
    }

    /**
     * Show the form to manually generate a new Micropub token.
     *
     * This is for clients (e.g. iA Writer) that don't support the IndieAuth
     * PKCE flow and instead expect to be given a token directly.
     */
    public function create(): View
    {
        return view('admin.tokens.create');
    }

    /**
     * Manually generate a new Micropub token.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'scope' => 'required|array|min:1',
        ]);

        $token = resolve(TokenService::class)->getNewToken([
            'me' => config('app.url'),
            'client_id' => $validated['client_id'],
            'scope' => implode(' ', $validated['scope']),
        ]);

        return redirect('/admin/tokens')->with('new_token', $token);
    }

    /**
     * Revoke a Micropub token.
     */
    public function revoke(MicropubToken $token): RedirectResponse
    {
        $token->revoke();

        return redirect('/admin/tokens');
    }
}
