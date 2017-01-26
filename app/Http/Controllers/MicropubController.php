<?php

namespace App\Http\Controllers;

use App\Place;
use Illuminate\Http\Request;
use App\Services\NoteService;
use Illuminate\Http\Response;
use App\Services\PlaceService;
use App\Services\TokenService;

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
                        try {
                            $note = $this->noteService->createNote($request, $clientId);
                        } catch (Exception $exception) {
                            return response()->json(['error' => true], 400);
                        }

                        return response()->json([
                            'response' => 'created',
                            'location' => $note->longurl,
                        ], 201)->header('Location', $note->longurl);
                    }
                    if ($request->input('h') == 'card' || $request->input('type')[0] == 'h-card') {
                        try {
                            $place = $this->placeService->createPlace($request);
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
    public function getEndpoint(Request $request)
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
        $content = 'No OAuth token sent with request.';
        $content = <<<'EOD'
{
    "response": "error",
    "error": "no_token",
    "error_description": "No token provided with request"
}
EOD;

        return (new Response($content, 400))
                        ->header('Content-Type', 'application/json');
    }
}
