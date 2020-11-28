<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InvalidTokenException;
use App\Http\Responses\MicropubResponses;
use App\Models\Place;
use App\Services\Micropub\{HCardService, HEntryService, UpdateService};
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

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
     *
     * @return JsonResponse
     * @throws InvalidTokenException
     */
    public function post(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->input('access_token'));
        } catch (RequiredConstraintsViolated | InvalidTokenStructure) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->invalidTokenResponse();
        }

        if ($tokenData->claims()->has('scope') === false) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->tokenHasNoScopeResponse();
        }

        $this->logMicropubRequest(request()->all());

        if ((request()->input('h') == 'entry') || (request()->input('type.0') == 'h-entry')) {
            if (stristr($tokenData->claims()->get('scope'), 'create') === false) {
                $micropubResponses = new MicropubResponses();

                return $micropubResponses->insufficientScopeResponse();
            }
            $location = $this->hentryService->process(request()->all(), $this->getCLientId());

            return response()->json([
                'response' => 'created',
                'location' => $location,
            ], 201)->header('Location', $location);
        }

        if (request()->input('h') == 'card' || request()->input('type.0') == 'h-card') {
            if (stristr($tokenData->claims()->get('scope'), 'create') === false) {
                $micropubResponses = new MicropubResponses();

                return $micropubResponses->insufficientScopeResponse();
            }
            $location = $this->hcardService->process(request()->all());

            return response()->json([
                'response' => 'created',
                'location' => $location,
            ], 201)->header('Location', $location);
        }

        if (request()->input('action') == 'update') {
            if (stristr($tokenData->claims()->get('scope'), 'update') === false) {
                $micropubResponses = new MicropubResponses();

                return $micropubResponses->insufficientScopeResponse();
            }

            return $this->updateService->process(request()->all());
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
     *
     * @return JsonResponse
     */
    public function get(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->input('access_token'));
        } catch (RequiredConstraintsViolated | InvalidTokenStructure) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->invalidTokenResponse();
        }

        if (request()->input('q') === 'syndicate-to') {
            return response()->json([
                'syndicate-to' => config('syndication.targets'),
            ]);
        }

        if (request()->input('q') == 'config') {
            return response()->json([
                'syndicate-to' => config('syndication.targets'),
                'media-endpoint' => route('media-endpoint'),
            ]);
        }

        if (request()->has('q') && substr(request()->input('q'), 0, 4) === 'geo:') {
            preg_match_all(
                '/([0-9.\-]+)/',
                request()->input('q'),
                $matches
            );
            $distance = (count($matches[0]) == 3) ? 100 * $matches[0][2] : 1000;
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
     * @return string
     * @throws RequiredConstraintsViolated
     */
    private function getClientId(): string
    {
        return resolve(TokenService::class)
            ->validateToken(request()->input('access_token'))
            ->claims()->get('client_id');
    }

    /**
     * Save the details of the micropub request to a log file.
     *
     * @param array $request This is the info from request()->all()
     */
    private function logMicropubRequest(array $request)
    {
        $logger = new Logger('micropub');
        $logger->pushHandler(new StreamHandler(storage_path('logs/micropub.log')));
        $logger->debug('MicropubLog', $request);
    }
}
