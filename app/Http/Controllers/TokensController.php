<?php

namespace App\Http\Controllers;

use App\Services\TokenService;

class TokensController extends Controller
{
    /**
     * The token service container.
     *
     * @var string
     */
    protected $tokenService;

    /**
     * Inject the service dependency.
     *
     * @return void
     */
    public function __construct(TokenService $tokenService = null)
    {
        $this->tokenService = $tokenService ?? new TokenService();
    }

    /**
     * Show all the saved tokens.
     *
     * @return \Illuminate\View\Factory view
     */
    public function showTokens()
    {
        $tokens = $$his->tokenService->getAll();

        return view('admin.listtokens', ['tokens' => $tokens]);
    }

    /**
     * Show the form to delete a certain token.
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function deleteToken($tokenId)
    {
        return view('admin.deletetoken', ['id' => $tokenId]);
    }

    /**
     * Process the request to delete a token.
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteToken($tokenId)
    {
        $this->tokenService->deleteToken($tokenId);

        return view('admin.deletetokensuccess', ['id' => $tokenId]);
    }
}
