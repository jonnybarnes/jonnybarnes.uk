<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TokenService;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use IndieAuth\Client;
use JsonException;

class TokenEndpointController extends Controller
{
    /**
     * @var Client The IndieAuth Client.
     */
    protected Client $client;

    /**
     * @var GuzzleClient The GuzzleHttp client.
     */
    protected GuzzleClient $guzzle;

    protected TokenService $tokenService;

    /**
     * Inject the dependencies.
     */
    public function __construct(
        Client $client,
        GuzzleClient $guzzle,
        TokenService $tokenService
    ) {
        $this->client = $client;
        $this->guzzle = $guzzle;
        $this->tokenService = $tokenService;
    }

    /**
     * If the user has auth’d via the IndieAuth protocol, issue a valid token.
     */
    public function create(Request $request): JsonResponse
    {
        $auth = $this->verifyIndieAuthCode(
            config('app.authorization_endpoint'),
            $request->input('code'),
            $request->input('redirect_uri'),
            $request->input('client_id'),
        );

        if ($auth === null || ! array_key_exists('me', $auth)) {
            return response()->json([
                'error' => 'There was an error verifying the IndieAuth code',
            ], 401);
        }

        $scope = $auth['scope'] ?? '';
        $tokenData = [
            'me' => config('app.url'),
            'client_id' => $request->input('client_id'),
            'scope' => $scope,
        ];
        $token = $this->tokenService->getNewToken($tokenData);
        $content = [
            'me' => config('app.url'),
            'scope' => $scope,
            'access_token' => $token,
        ];

        return response()->json($content);
    }

    protected function verifyIndieAuthCode(
        string $authorizationEndpoint,
        string $code,
        string $redirectUri,
        string $clientId
    ): ?array {
        try {
            $response = $this->guzzle->request('POST', $authorizationEndpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'code' => $code,
                    'me' => config('app.url'),
                    'redirect_uri' => $redirectUri,
                    'client_id' => $clientId,
                ],
            ]);
        } catch (BadResponseException) {
            return null;
        }

        try {
            $authData = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return $authData;
    }
}
