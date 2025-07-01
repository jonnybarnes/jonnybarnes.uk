<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InvalidTokenScopeException;
use App\Exceptions\MicropubHandlerException;
use App\Http\Requests\MicropubRequest;
use App\Models\Place;
use App\Models\SyndicationTarget;
use App\Services\Micropub\MicropubHandlerRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lcobucci\JWT\Token;

class MicropubController extends Controller
{
    protected MicropubHandlerRegistry $handlerRegistry;

    public function __construct(MicropubHandlerRegistry $handlerRegistry)
    {
        $this->handlerRegistry = $handlerRegistry;
    }

    /**
     * Respond to a POST request to the micropub endpoint.
     *
     * The request is initially processed by the MicropubRequest form request
     * class. The normalizes the data, so we can pass it into the handlers for
     * the different micropub requests, h-entry or h-card, for example.
     */
    public function post(MicropubRequest $request): JsonResponse
    {
        $type = $request->getType();

        if (! $type) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Microformat object type is missing, for example: h-entry or h-card',
            ], 400);
        }

        try {
            $handler = $this->handlerRegistry->getHandler($type);
            $result = $handler->handle($request->getMicropubData());

            // Return appropriate response based on the handler result
            return response()->json([
                'response' => $result['response'],
                'location' => $result['url'] ?? null,
            ], 201)->header('Location', $result['url']);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => $e->getMessage(),
            ], 400);
        } catch (MicropubHandlerException) {
            return response()->json([
                'error' => 'Unknown Micropub type',
                'error_description' => 'The request could not be processed by this server',
            ], 500);
        } catch (InvalidTokenScopeException) {
            return response()->json([
                'error' => 'invalid_scope',
                'error_description' => 'The token does not have the required scope for this request',
            ], 403);
        } catch (\Exception) {
            return response()->json([
                'error' => 'server_error',
                'error_description' => 'An error occurred processing the request',
            ], 500);
        }
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

        // the default response is just to return the token data
        /** @var Token $tokenData */
        $tokenData = $request->input('token_data');

        return response()->json([
            'response' => 'token',
            'token' => [
                'me' => $tokenData['me'],
                'scope' => $tokenData['scope'],
                'client_id' => $tokenData['client_id'],
            ],
        ]);
    }
}
