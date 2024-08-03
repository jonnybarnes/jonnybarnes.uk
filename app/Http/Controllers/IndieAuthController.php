<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TokenService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Random\RandomException;
use SodiumException;

class IndieAuthController extends Controller
{
    public function indieAuthMetadataEndpoint(): JsonResponse
    {
        return response()->json([
            'issuer' => config('app.url'),
            'authorization_endpoint' => route('indieauth.start'),
            'token_endpoint' => route('indieauth.token'),
            'code_challenge_methods_supported' => ['S256'],
            //'introspection_endpoint' => route('indieauth.introspection'),
            //'introspection_endpoint_auth_methods_supported' => ['none'],
        ]);
    }

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

        $scopes = $request->get('scope', '');
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
    public function confirm(Request $request): RedirectResponse
    {
        $authCode = bin2hex(random_bytes(16));

        $cacheKey = hash('xxh3', $request->get('client_id'));

        $indieAuthRequestData = [
            'code_challenge' => $request->get('code_challenge'),
            'code_challenge_method' => $request->get('code_challenge_method'),
            'client_id' => $request->get('client_id'),
            'redirect_uri' => $request->get('redirect_uri'),
            'auth_code' => $authCode,
            'scope' => implode(' ', $request->get('scope', '')),
        ];

        Cache::put($cacheKey, $indieAuthRequestData, now()->addMinutes(10));

        $redirectUri = new Uri($request->get('redirect_uri'));
        $redirectUri = Uri::withQueryValues($redirectUri, [
            'code' => $authCode,
            'state' => $request->get('state'),
            'iss' => config('app.url'),
        ]);

        return redirect()->away($redirectUri);
    }

    /**
     * Process a POST request to the IndieAuth auth endpoint.
     *
     * This is one possible second step in the IndieAuth flow, where the client app sends the auth code to the IndieAuth
     * endpoint. As it is to the auth endpoint we return profile information. A similar request can be made to the token
     * endpoint to get an access token.
     */
    public function processCodeExchange(Request $request): JsonResponse
    {
        $invalidCodeResponse = $this->validateAuthorizationCode($request);

        if ($invalidCodeResponse instanceof JsonResponse) {
            return $invalidCodeResponse;
        }

        return response()->json([
            'me' => config('app.url'),
        ]);
    }

    /**
     * Process a POST request to the IndieAuth token endpoint.
     *
     * This is another possible second step in the IndieAuth flow, where the client app sends the auth code to the
     * IndieAuth token endpoint. As it is to the token endpoint we return an access token.
     *
     * @throws SodiumException
     */
    public function processTokenRequest(Request $request): JsonResponse
    {
        $indieAuthData = $this->validateAuthorizationCode($request);

        if ($indieAuthData instanceof JsonResponse) {
            return $indieAuthData;
        }

        if ($indieAuthData['scope'] === '') {
            return response()->json(['errors' => [
                'scope' => [
                    'The scope property must be non-empty for an access token to be issued.',
                ],
            ]], 400);
        }

        $tokenData = [
            'me' => config('app.url'),
            'client_id' => $request->get('client_id'),
            'scope' => $indieAuthData['scope'],
        ];
        $tokenService = resolve(TokenService::class);
        $token = $tokenService->getNewToken($tokenData);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'scope' => $indieAuthData['scope'],
            'me' => config('app.url'),
        ]);
    }

    protected function isValidRedirectUri(string $clientId, string $redirectUri): bool
    {
        // If client_id is not a valid URL, then it's not valid
        $clientIdParsed = \Mf2\parseUriToComponents($clientId);
        if (! isset($clientIdParsed['authority'])) {
            return false;
        }

        // If redirect_uri is not a valid URL, then it's not valid
        $redirectUriParsed = \Mf2\parseUriToComponents($redirectUri);
        if (! isset($redirectUriParsed['authority'])) {
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
        } catch (Exception) {
            return false;
        }

        $clientInfoParsed = \Mf2\parse($clientInfo->getBody()->getContents(), $clientId);

        $redirectUris = $clientInfoParsed['rels']['redirect_uri'] ?? [];

        return in_array($redirectUri, $redirectUris, true);
    }

    /**
     * @throws SodiumException
     */
    protected function validateAuthorizationCode(Request $request): JsonResponse|array
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
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if ($request->get('grant_type') !== 'authorization_code') {
            return response()->json(['errors' => [
                'grant_type' => [
                    'Only a grant type of "authorization_code" is supported.',
                ],
            ]], 400);
        }

        // Check cache for auth code
        $cacheKey = hash('xxh3', $request->get('client_id'));
        $indieAuthRequestData = Cache::pull($cacheKey);

        if ($indieAuthRequestData === null) {
            return response()->json(['errors' => [
                'code' => [
                    'The code is invalid.',
                ],
            ]], 404);
        }

        // Check the IndieAuth code
        if (! array_key_exists('auth_code', $indieAuthRequestData)) {
            return response()->json(['errors' => [
                'code' => [
                    'The code is invalid.',
                ],
            ]], 400);
        }
        if ($indieAuthRequestData['auth_code'] !== $request->get('code')) {
            return response()->json(['errors' => [
                'code' => [
                    'The code is invalid.',
                ],
            ]], 400);
        }

        // Check code verifier
        if (! array_key_exists('code_challenge', $indieAuthRequestData)) {
            return response()->json(['errors' => [
                'code_verifier' => [
                    'The code verifier is invalid.',
                ],
            ]], 400);
        }
        if (! hash_equals(
            $indieAuthRequestData['code_challenge'],
            sodium_bin2base64(
                hash('sha256', $request->get('code_verifier'), true),
                SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
            )
        )) {
            return response()->json(['errors' => [
                'code_verifier' => [
                    'The code verifier is invalid.',
                ],
            ]], 400);
        }

        // Check redirect_uri
        if (! array_key_exists('redirect_uri', $indieAuthRequestData)) {
            return response()->json(['errors' => [
                'redirect_uri' => [
                    'The redirect uri is invalid.',
                ],
            ]], 400);
        }
        if ($indieAuthRequestData['redirect_uri'] !== $request->get('redirect_uri')) {
            return response()->json(['errors' => [
                'redirect_uri' => [
                    'The redirect uri is invalid.',
                ],
            ]], 400);
        }

        // Check client_id
        if (! array_key_exists('client_id', $indieAuthRequestData)) {
            return response()->json(['errors' => [
                'client_id' => [
                    'The client id is invalid.',
                ],
            ]], 400);
        }
        if ($indieAuthRequestData['client_id'] !== $request->get('client_id')) {
            return response()->json(['errors' => [
                'client_id' => [
                    'The client id is invalid.',
                ],
            ]], 400);
        }

        return $indieAuthRequestData;
    }
}
