<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MicropubToken;
use Illuminate\Http\RedirectResponse;
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
     * Revoke a Micropub token.
     */
    public function revoke(MicropubToken $token): RedirectResponse
    {
        $token->revoke();

        return redirect('/admin/tokens');
    }
}
