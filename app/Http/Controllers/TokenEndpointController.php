<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use IndieAuth\Client;

class TokenEndpointController extends Controller
{
    /**
     * The IndieAuth Client.
     */
    protected Client $client;

    /**
     * The Token handling service.
     */
    protected TokenService $tokenService;

    /**
     * Inject the dependencies.
     *
     * @param  Client  $client
     * @param  TokenService  $tokenService
     */
    public function __construct(
        Client $client,
        TokenService $tokenService
    ) {
        $this->client = $client;
        $this->tokenService = $tokenService;
    }

    /**
     * If the user has auth’d via the IndieAuth protocol, issue a valid token.
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $authorizationEndpoint = $this->client->discoverAuthorizationEndpoint(normalize_url(request()->input('me')));
        if ($authorizationEndpoint) {
            $auth = $this->client->verifyIndieAuthCode(
                $authorizationEndpoint,
                request()->input('code'),
                request()->input('me'),
                request()->input('redirect_uri'),
                request()->input('client_id'),
                null // code_verifier
            );
            if (array_key_exists('me', $auth)) {
                $scope = $auth['scope'] ?? '';
                $tokenData = [
                    'me' => request()->input('me'),
                    'client_id' => request()->input('client_id'),
                    'scope' => $scope,
                ];
                $token = $this->tokenService->getNewToken($tokenData);
                $content = [
                    'me' => request()->input('me'),
                    'scope' => $scope,
                    'access_token' => $token,
                ];

                return response()->json($content);
            }

            return response()->json([
                'error' => 'There was an error verifying the authorisation code.',
            ], 401);
        }

        return response()->json([
            'error' => 'Can’t determine the authorisation endpoint.',
        ], 400);
    }
}
