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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            /** @var MicropubData $data */
            $data = $dataClass::fromRequest($request);
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
            ], 401);
        } catch (ModelNotFoundException) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'No known note with given ID',
            ], 404);
        } catch (MicropubUnsupportedModelException $e) {
            report($e);

            return response()->json([
                'error' => 'invalid',
                'error_description' => 'This implementation currently only supports the updating of notes',
            ], 500);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => $e->getMessage(),
            ], 400);
        } catch (MicropubHandlerException $e) {
            report($e);

            return response()->json([
                'error' => 'unsupported_operation',
                'error_description' => 'The request could not be processed by this server',
            ], 500);
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
     * Supports q=syndicate-to, q=config, and q=geo:<lat>,<lng> queries.
     * The default response returns the token metadata.
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
}
