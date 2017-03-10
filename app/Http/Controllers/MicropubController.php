<?php

namespace App\Http\Controllers;

use App\Media;
use App\Place;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use App\Services\NoteService;
use Illuminate\Http\Response;
use App\Services\PlaceService;
use App\Services\TokenService;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

class MicropubController extends Controller
{
    /**
     * The Token service container.
     */
    protected $tokenService;

    /**
     * The Note service container.
     */
    protected $noteService;

    /**
     * The Place service container.
     */
    protected $placeService;

    /**
     * Inject the dependencies.
     */
    public function __construct(
        TokenService $tokenService = null,
        NoteService $noteService = null,
        PlaceService $placeService = null
    ) {
        $this->tokenService = $tokenService ?? new TokenService();
        $this->noteService = $noteService ?? new NoteService();
        $this->placeService = $placeService ?? new PlaceService();
    }

    /**
     * This function receives an API request, verifies the authenticity
     * then passes over the info to the relavent Service class.
     *
     * @param  \Illuminate\Http\Request request
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        $httpAuth = $request->header('Authorization');
        if (preg_match('/Bearer (.+)/', $httpAuth, $match)) {
            $token = $match[1];
            $tokenData = $this->tokenService->validateToken($token);
            if ($tokenData->hasClaim('scope')) {
                $scopes = explode(' ', $tokenData->getClaim('scope'));
                if (array_search('post', $scopes) !== false) {
                    $clientId = $tokenData->getClaim('client_id');
                    if (($request->input('h') == 'entry') || ($request->input('type')[0] == 'h-entry')) {
                        $data = [];
                        $data['client-id'] = $clientId;
                        if ($request->header('Content-Type') == 'application/json') {
                            $data['content'] = $request->input('properties.content')[0];
                            $data['in-reply-to'] = $request->input('properties.in-reply-to')[0];
                            $data['location'] = $request->input('properties.location');
                            //flatten location if array
                            if (is_array($data['location'])) {
                                $data['location'] = $data['location'][0];
                            }
                        } else {
                            $data['content'] = $request->input('content');
                            $data['in-reply-to'] = $request->input('in-reply-to');
                            $data['location'] = $request->input('location');
                        }
                        $data['syndicate'] = [];
                        $targets = array_pluck(config('syndication.targets'), 'uid', 'service.name');
                        if (is_string($request->input('mp-syndicate-to'))) {
                            $service = array_search($request->input('mp-syndicate-to'));
                            if ($service == 'Twitter') {
                                $data['syndicate'][] = 'twitter';
                            }
                            if ($service == 'Facebook') {
                                $data['syndicate'][] = 'facebook';
                            }
                        }
                        if (is_array($request->input('mp-syndicate-to'))) {
                            foreach ($targets as $service => $target) {
                                if (in_array($target, $request->input('mp-syndicate-to'))) {
                                    if ($service == 'Twitter') {
                                        $data['syndicate'][] = 'twitter';
                                    }
                                    if ($service == 'Facebook') {
                                        $data['syndicate'][] = 'facebook';
                                    }
                                }
                            }
                        }
                        try {
                            $note = $this->noteService->createNote($data);
                        } catch (Exception $exception) {
                            return response()->json(['error' => true], 400);
                        }

                        return response()->json([
                            'response' => 'created',
                            'location' => $note->longurl,
                        ], 201)->header('Location', $note->longurl);
                    }
                    if ($request->input('h') == 'card' || $request->input('type')[0] == 'h-card') {
                        $data = [];
                        if ($request->header('Content-Type') == 'application/json') {
                            $data['name'] = $request->input('properties.name');
                            $data['description'] = $request->input('properties.description') ?? null;
                            if ($request->has('properties.geo')) {
                                $data['geo'] = $request->input('properties.geo');
                            }
                        } else {
                            $data['name'] = $request->input('name');
                            $data['description'] = $request->input('description');
                            if ($request->has('geo')) {
                                $data['geo'] = $request->input('geo');
                            }
                            if ($request->has('latitude')) {
                                $data['latitude'] = $request->input('latitude');
                                $data['longitude'] = $request->input('longitude');
                            }
                        }
                        try {
                            $place = $this->placeService->createPlace($data);
                        } catch (Exception $exception) {
                            return response()->json(['error' => true], 400);
                        }

                        return response()->json([
                            'response' => 'created',
                            'location' => $place->longurl,
                        ], 201)->header('Location', $place->longurl);
                    }
                }
            }

            return response()->json([
                'response' => 'error',
                'error' => 'invalid_token',
                'error_description' => 'The token provided is not valid or does not have the necessary scope',
            ], 400);
        }

        return response()->json([
            'response' => 'error',
            'error' => 'no_token',
            'error_description' => 'No OAuth token sent with request',
        ], 400);
    }

    /**
     * A GET request has been made to `api/post` with an accompanying
     * token, here we check wether the token is valid and respond
     * appropriately. Further if the request has the query parameter
     * synidicate-to we respond with the known syndication endpoints.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        $httpAuth = $request->header('Authorization');
        if (preg_match('/Bearer (.+)/', $httpAuth, $match)) {
            $token = $match[1];
            $valid = $this->tokenService->validateToken($token);

            if ($valid === null) {
                return response()->json([
                    'response' => 'error',
                    'error' => 'invalid_token',
                    'error_description' => 'The provided token did not pass validation',
                ], 400);
            }
            //we have a valid token, is `syndicate-to` set?
            if ($request->input('q') === 'syndicate-to') {
                return response()->json([
                    'syndicate-to' => config('syndication.targets'),
                ]);
            }
            //nope, how about a geo URL?
            if (substr($request->input('q'), 0, 4) === 'geo:') {
                preg_match_all(
                    '/([0-9\.\-]+)/',
                    $request->input('q'),
                    $matches
                );
                $distance = (count($matches[0]) == 3) ? 100 * $matches[0][2] : 1000;
                $places = Place::near($matches[0][0], $matches[0][1], $distance);
                foreach ($places as $place) {
                    $place->uri = config('app.url') . '/places/' . $place->slug;
                }

                return response()->json([
                    'response' => 'places',
                    'places' => $places,
                ]);
            }
            //nope, how about a config query?
            //this should have a media endpoint as well at some point
            if ($request->input('q') == 'config') {
                return response()->json([
                    'syndicate-to' => config('syndication.targets'),
                ]);
            }

            //nope, just return the token
            return response()->json([
                'response' => 'token',
                'token' => [
                    'me' => $valid->getClaim('me'),
                    'scope' => $valid->getClaim('scope'),
                    'client_id' => $valid->getClaim('client_id'),
                ],
            ]);
        }

        return response()->json([
            'response' => 'error',
            'error' => 'no_token',
            'error_description' => 'No token provided with request',
        ], 400);
    }

    /**
     * Process a media item posted to the media endpoint.
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function media(Request $request)
    {
        //can this go in middleware
        $httpAuth = $request->header('Authorization');
        if (preg_match('/Bearer (.+)/', $httpAuth, $match)) {
            $token = $match[1];
            $tokenData = $this->tokenService->validateToken($token);

            if ($tokenData === null) {
                return response()->json([
                    'response' => 'error',
                    'error' => 'invalid_token',
                    'error_description' => 'The provided token did not pass validation',
                ], 400);
            }

            //check post scope
            if ($tokenData->hasClaim('scope')) {
                $scopes = explode(' ', $tokenData->getClaim('scope'));
                if (array_search('post', $scopes) !== false) {
                    //check media valid
                    if ($request->file('file')->isValid()) {
                        //save media
                        try {
                            $filename = Uuid::uuid4() . '.' . $request->file('file')->extension();
                        } catch (UnsatisfiedDependencyException $e) {
                            return response()->json([
                                'response' => 'error',
                                'error' => 'internal_server_error',
                                'error_description' => 'A problem occured handling your request'
                            ], 500);
                        }
                        try {
                            $path = $request->file('file')->storeAs('media', $filename, 's3');
                        } catch (Exception $e) { // which exception?
                            return response()->json([
                                'response' => 'error',
                                'error' => 'service_unavailable',
                                'error_description' => 'Unable to save media to S3'
                            ], 503);
                        }
                        $media = new Media();
                        $media->token = $token;
                        $media->path = $path;
                        $media->save();

                        return response()->json([
                            'response' => 'created',
                            'location' => $media->url,
                        ], 201)->header('Location', $media->url);
                    }

                    return response()->json([
                        'response' => 'error',
                        'error' => 'invalid_request',
                        'error_description' => 'The uploaded file failed validation',
                    ], 400);
                }

                return response()->json([
                    'response' => 'error',
                    'error' => 'insufficient_scope',
                    'error_description' => 'The provided token has insufficient scopes',
                ], 401);
            }

            return response()->json([
                'response' => 'error',
                'error' => 'unauthorized',
                'error_description' => 'No token provided with request',
            ], 401);
        }

        return response()->json([
            'response' => 'error',
            'error' => 'no_token',
            'error_description' => 'There was no token provided with the request'
        ], 400);
    }
}
