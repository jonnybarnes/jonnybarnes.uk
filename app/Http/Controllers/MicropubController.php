<?php

namespace App\Http\Controllers;

use App\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\NoteService;
use App\Services\TokenService;
use App\Services\PlaceService;

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
     * @todo   Move the syndication endpoints into a .env variable
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
                    'error_description' => 'The provided token did not pass validation'
                ], 400);
            }
            //we have a valid token, is `syndicate-to` set?
            if ($request->input('q') === 'syndicate-to') {
                return response()->json([
                    'syndicate-to' => [[
                        'uid' => 'https://twitter.com/jonnybarnes',
                        'name' => 'jonnybarnes on Twitter',
                        'service' => [
                            'name' => 'Twitter',
                            'url' => 'https://twitter.com',
                            'photo' => 'https://upload.wikimedia.org/wikipedia/en/9/9f/Twitter_bird_logo_2012.svg',
                        ],
                        'user' => [
                            'name' => 'jonnybarnes',
                            'url' => 'https://twitter.com/jonnybarnes',
                            'photo' => 'https://pbs.twimg.com/profile_images/1853565405/jmb-bw.jpg',
                        ],
                    ]],
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

                return response()->json([
                    'response' => 'places',
                    'places' => $places,
                ]);
            }
            //nope, ho about a config query?
            if ($request->input('q') == 'config') {
                return response()->json([
                    'syndicate-to' => [[
                        'uid' => 'https://twitter.com/jonnybarnes',
                        'name' => 'jonnybarnes on Twitter',
                        'service' => [
                            'name' => 'Twitter',
                            'url' => 'https://twitter.com',
                            'photo' => 'https://upload.wikimedia.org/wikipedia/en/9/9f/Twitter_bird_logo_2012.svg',
                        ],
                        'user' => [
                            'name' => 'jonnybarnes',
                            'url' => 'https://twitter.com/jonnybarnes',
                            'photo' => 'https://pbs.twimg.com/profile_images/1853565405/jmb-bw.jpg',
                        ],
                    ]],
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
