<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Random\RandomException;
use SodiumException;

class IndieAuthController extends Controller
{
    /**
     * Process a GET request to the IndieAuth endpoint.
     *
     * This is the first step in the IndieAuth flow, where the client app sends the user to the IndieAuth endpoint.
     */
    public function start(Request $request): View
    {
        // First check all required params are present
        $validator = Validator::make($request->all(), [
            'response_type' => 'required:string',
            'client_id' => 'required',
            'redirect_uri' => 'required',
            'state' => 'required',
            'code_challenge' => 'required:string',
            'code_challenge_method' => 'required:string',
        ], [
            'response_type' => 'response_type is required',
            'client_id.required' => 'client_id is required to display which app is asking for authentication',
            'redirect_uri.required' => 'redirect_uri is required so we can progress successful requests',
            'state.required' => 'state is required',
            'code_challenge.required' => 'code_challenge is required',
            'code_challenge_method.required' => 'code_challenge_method is required',
        ]);

        if ($validator->fails()) {
            return view('indieauth.error')->withErrors($validator);
        }

        if ($request->get('response_type') !== 'code') {
            return view('indieauth.error')->withErrors(['response_type' => 'only a response_type of "code" is supported']);
        }

        if (mb_strtoupper($request->get('code_challenge_method')) !== 'S256') {
            return view('indieauth.error')->withErrors(['code_challenge_method' => 'only a code_challenge_method of "S256" is supported']);
        }

        if (! $this->isValidRedirectUri($request->get('client_id'), $request->get('redirect_uri'))) {
            return view('indieauth.error')->withErrors(['redirect_uri' => 'redirect_uri is not valid for this client_id']);
        }

        $scopes = $request->get('scopes', '');
        $scopes = explode(' ', $scopes);

        return view('indieauth.start', [
            'me' => $request->get('me'),
            'client_id' => $request->get('client_id'),
            'redirect_uri' => $request->get('redirect_uri'),
            'state' => $request->get('state'),
            'scopes' => $scopes,
            'code_challenge' => $request->get('code_challenge'),
            'code_challenge_method' => $request->get('code_challenge_method'),
        ]);
    }

    /**
     * Confirm an IndieAuth approval request.
     *
     * Generates an auth code and redirects the user back to the client app.
     *
     * @throws RandomException
     */
    public function confirm(Request $request): JsonResponse
    {
        $authCode = bin2hex(random_bytes(16));

        $cacheKey = hash('xxh3', $request->get('client_id'));

        $indieAuthRequestData = [
            'code_challenge' => $request->get('code_challenge'),
            'code_challenge_method' => $request->get('code_challenge_method'),
            'client_id' => $request->get('client_id'),
            'auth_code' => $authCode,
        ];

        Cache::put($cacheKey, $indieAuthRequestData, now()->addMinutes(10));

        $redirectUri = new Uri($request->get('redirect_uri'));
        $redirectUri = Uri::withQueryValues($redirectUri, [
            'code' => $authCode,
            'me' => $request->get('me'),
            'state' => $request->get('state'),
        ]);

        // For now just dump URL scheme
        return response()->json([
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * Process a POST request to the IndieAuth endpoint.
     *
     * This is the second step in the IndieAuth flow, where the client app sends the auth code to the IndieAuth endpoint.
     * @throws SodiumException
     */
    public function processCodeExchange(Request $request): JsonResponse
    {
        // First check all the data is present
        $validator = Validator::make($request->all(), [
            'grant_type' => 'required:string',
            'code' => 'required:string',
            'client_id' => 'required',
            'redirect_uri' => 'required',
            'code_verifier' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if ($request->get('grant_type') !== 'authorization_code') {
            return response()->json(['error' => 'only a grant_type of "authorization_code" is supported'], 400);
        }

        // Check cache for auth code
        $cacheKey = hash('xxh3', $request->get('client_id'));
        $indieAuthRequestData = Cache::pull($cacheKey);

        if ($indieAuthRequestData === null) {
            return response()->json(['error' => 'code is invalid'], 404);
        }

        if ($indieAuthRequestData['auth_code'] !== $request->get('code')) {
            return response()->json(['error' => 'code is invalid'], 400);
        }

        // Check code verifier
        if (! hash_equals(
            $indieAuthRequestData['code_challenge'],
            sodium_bin2base64(
                hash('sha256', $request->get('code_verifier'), true),
                SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
            )
        )) {
            return response()->json(['error' => 'code_verifier is invalid'], 400);
        }

        return response()->json([
            'me' => config('app.url'),
        ]);
    }

    protected function isValidRedirectUri(string $clientId, string $redirectUri): bool
    {
        // If client_id is not a valid URL, then it's not valid
        $clientIdParsed = \Mf2\parseUriToComponents($clientId);
        if (! isset($clientIdParsed['authority'])) {
            ray($clientIdParsed);

            return false;
        }

        // If redirect_uri is not a valid URL, then it's not valid
        $redirectUriParsed = \Mf2\parseUriToComponents($redirectUri);
        if (! isset($redirectUriParsed['authority'])) {
            ray($redirectUriParsed);

            return false;
        }

        // If client_id and redirect_uri are the same host, then it's valid
        if ($clientIdParsed['authority'] === $redirectUriParsed['authority']) {
            return true;
        }

        // Otherwise we need to check the redirect_uri is in the client_id's redirect_uris
        $guzzle = resolve(Client::class);

        try {
            $clientInfo = $guzzle->get($clientId);
        } catch (Exception $e) {
            ray('Failed to fetch client info', $e->getMessage());

            return false;
        }

        $clientInfoParsed = \Mf2\parse($clientInfo->getBody()->getContents(), $clientId);

        $redirectUris = $clientInfoParsed['rels']['redirect_uri'] ?? [];

        return in_array($redirectUri, $redirectUris);
    }
}
