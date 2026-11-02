<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InvalidTokenScopeException;
use App\Exceptions\MicropubHandlerException;
use App\Exceptions\MicropubUnsupportedModelException;
use App\Http\Requests\MicropubRequest;
use App\Models\Place;
use App\Models\SyndicationTarget;
use App\Services\Micropub\Data\MicropubData;
use App\Services\Micropub\MicropubHandlerRegistry;
use App\Services\Micropub\NoteResolver;
use App\Services\Micropub\NoteSource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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
     * MicropubRequest detects the request type (e.g. entry, card, update).
     * The handler registry resolves the appropriate handler, whose DTO
     * extracts the relevant fields from the request via fromRequest().
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
            $dataClass = $handler->dataClass();

            try {
                /** @var MicropubData $data */
                $data = $dataClass::fromRequest($request);
            } catch (\TypeError) {
                // the data objects are typed, so this means a property has the wrong shape
                return $this->invalidRequest('A property in the request has an invalid value');
            }

            $result = $handler->handle($data);

            if ($result['response'] === 'updated') {
                return response()->json([
                    'response' => $result['response'],
                ], 200)->header('Location', $result['url']);
            }

            return response()->json([
                'response' => $result['response'],
                'location' => $result['url'] ?? null,
            ], 201)->header('Location', $result['url']);
        } catch (InvalidTokenScopeException) {
            return response()->json([
                'error' => 'insufficient_scope',
                'error_description' => 'The token does not have the required scope for this request',
            ], 403);
        } catch (ModelNotFoundException) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'No known note with given ID',
            ], 404);
        } catch (MicropubUnsupportedModelException|MicropubHandlerException|\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'server_error',
                'error_description' => 'An error occurred processing the request',
            ], 500);
        }
    }

    /**
     * Respond to a GET request to the micropub endpoint.
     *
     * Token validation is handled by the VerifyMicropubToken middleware.
     * Supports q=syndicate-to, q=config, q=source, and q=geo:<lat>,<lng> queries.
     * The default response returns the token metadata.
     */
    public function get(Request $request): JsonResponse
    {
        $q = $request->input('q');

        if ($q === 'syndicate-to') {
            return response()->json([
                'syndicate-to' => SyndicationTarget::all(),
            ]);
        }

        if ($q === 'config') {
            return response()->json([
                'syndicate-to' => SyndicationTarget::all(),
                'media-endpoint' => route('media-endpoint'),
            ]);
        }

        if ($q === 'source') {
            return $this->sourceResponse($request);
        }

        if (is_string($q) && str_starts_with($q, 'geo:')) {
            return $this->placesResponse($q);
        }

        if ($request->has('q')) {
            return $this->invalidRequest('This server does not know how to handle this q parameter');
        }

        // the default response is just to return the token data
        /** @var array $tokenData */
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

    /**
     * Respond to a q=source query with the microformats2 JSON of a post.
     */
    private function sourceResponse(Request $request): JsonResponse
    {
        $url = $request->input('url');

        if (! is_string($url)) {
            return $this->invalidRequest('The url parameter is required');
        }

        try {
            $note = resolve(NoteResolver::class)->fromUrl($url);
        } catch (MicropubUnsupportedModelException $e) {
            return $this->invalidRequest($e->getMessage());
        } catch (ModelNotFoundException) {
            return $this->invalidRequest('No known note with given ID', 404);
        }

        $requested = array_values(array_filter(Arr::wrap($request->input('properties')), 'is_string'));

        return response()->json(resolve(NoteSource::class)->toArray($note, $requested));
    }

    /**
     * Respond to a q=geo:<lat>,<lng> query with the nearby places.
     */
    private function placesResponse(string $q): JsonResponse
    {
        preg_match_all('/([0-9.\-]+)/', $q, $matches);

        if (count($matches[0]) < 2 || count(array_filter($matches[0], 'is_numeric')) !== count($matches[0])) {
            return $this->invalidRequest('The geo query must contain a latitude and longitude');
        }

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

    private function invalidRequest(string $description, int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => 'invalid_request',
            'error_description' => $description,
        ], $status);
    }
}
