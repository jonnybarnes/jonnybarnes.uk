<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Responses\MicropubResponses;
use App\Models\Place;
use App\Models\SyndicationTarget;
use App\Services\Micropub\HCardService;
use App\Services\Micropub\HEntryService;
use App\Services\Micropub\UpdateService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @psalm-suppress UnusedClass
 */
class MicropubController extends Controller
{
    protected TokenService $tokenService;

    protected HEntryService $hentryService;

    protected HCardService $hcardService;

    protected UpdateService $updateService;

    public function __construct(
        TokenService $tokenService,
        HEntryService $hentryService,
        HCardService $hcardService,
        UpdateService $updateService
    ) {
        $this->tokenService = $tokenService;
        $this->hentryService = $hentryService;
        $this->hcardService = $hcardService;
        $this->updateService = $updateService;
    }

    /**
     * This function receives an API request, verifies the authenticity
     * then passes over the info to the relevant Service class.
     */
    public function post(Request $request): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken($request->input('access_token'));
        } catch (RequiredConstraintsViolated|InvalidTokenStructure|CannotDecodeContent) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->invalidTokenResponse();
        }

        if ($tokenData->claims()->has('scope') === false) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->tokenHasNoScopeResponse();
        }

        $this->logMicropubRequest($request->all());

        if (($request->input('h') === 'entry') || ($request->input('type.0') === 'h-entry')) {
            $scopes = $tokenData->claims()->get('scope');
            if (is_string($scopes)) {
                $scopes = explode(' ', $scopes);
            }

            if (! in_array('create', $scopes)) {
                $micropubResponses = new MicropubResponses();

                return $micropubResponses->insufficientScopeResponse();
            }
            $location = $this->hentryService->process($request->all(), $this->getCLientId());

            return response()->json([
                'response' => 'created',
                'location' => $location,
            ], 201)->header('Location', $location);
        }

        if ($request->input('h') === 'card' || $request->input('type.0') === 'h-card') {
            $scopes = $tokenData->claims()->get('scope');
            if (is_string($scopes)) {
                $scopes = explode(' ', $scopes);
            }
            if (! in_array('create', $scopes)) {
                $micropubResponses = new MicropubResponses();

                return $micropubResponses->insufficientScopeResponse();
            }
            $location = $this->hcardService->process($request->all());

            return response()->json([
                'response' => 'created',
                'location' => $location,
            ], 201)->header('Location', $location);
        }

        if ($request->input('action') === 'update') {
            $scopes = $tokenData->claims()->get('scope');
            if (is_string($scopes)) {
                $scopes = explode(' ', $scopes);
            }
            if (! in_array('update', $scopes)) {
                $micropubResponses = new MicropubResponses();

                return $micropubResponses->insufficientScopeResponse();
            }

            return $this->updateService->process($request->all());
        }

        return response()->json([
            'response' => 'error',
            'error_description' => 'unsupported_request_type',
        ], 500);
    }

    /**
     * Respond to a GET request to the micropub endpoint.
     *
     * A GET request has been made to `api/post` with an accompanying
     * token, here we check whether the token is valid and respond
     * appropriately. Further if the request has the query parameter
     * syndicate-to we respond with the known syndication endpoints.
     */
    public function get(Request $request): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken($request->input('access_token'));
        } catch (RequiredConstraintsViolated|InvalidTokenStructure) {
            return (new MicropubResponses())->invalidTokenResponse();
        }

        if ($request->input('q') === 'syndicate-to') {
            return response()->json([
                'syndicate-to' => SyndicationTarget::all(),
            ]);
        }

        if ($request->input('q') === 'config') {
            return response()->json([
                'syndicate-to' => SyndicationTarget::all(),
                'media-endpoint' => route('media-endpoint'),
            ]);
        }

        if ($request->has('q') && str_starts_with($request->input('q'), 'geo:')) {
            preg_match_all(
                '/([0-9.\-]+)/',
                $request->input('q'),
                $matches
            );
            $distance = (count($matches[0]) === 3) ? 100 * $matches[0][2] : 1000;
            $places = Place::near(
                (object) ['latitude' => $matches[0][0], 'longitude' => $matches[0][1]],
                $distance
            )->get();

            return response()->json([
                'response' => 'places',
                'places' => $places,
            ]);
        }

        // default response is just to return the token data
        return response()->json([
            'response' => 'token',
            'token' => [
                'me' => $tokenData->claims()->get('me'),
                'scope' => $tokenData->claims()->get('scope'),
                'client_id' => $tokenData->claims()->get('client_id'),
            ],
        ]);
    }

    /**
     * Determine the client id from the access token sent with the request.
     *
     * @throws RequiredConstraintsViolated
     */
    private function getClientId(): string
    {
        return resolve(TokenService::class)
            ->validateToken(app('request')->input('access_token'))
            ->claims()->get('client_id');
    }

    /**
     * Save the details of the micropub request to a log file.
     */
    private function logMicropubRequest(array $request): void
    {
        $logger = new Logger('micropub');
        $logger->pushHandler(new StreamHandler(storage_path('logs/micropub.log')));
        $logger->debug('MicropubLog', $request);
    }
}
