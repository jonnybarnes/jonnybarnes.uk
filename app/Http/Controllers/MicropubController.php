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
     * Injest the dependency.
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
                    $type = $request->input('h');
                    if ($type == 'entry') {
                        $note = $this->noteService->createNote($request, $clientId);
                        $content = 'Note created at ' . $note->longurl;

                        return (new Response($content, 201))
                                      ->header('Location', $note->longurl);
                    }
                    if ($type == 'card') {
                        $place = $this->placeService->createPlace($request);
                        $content = 'Place created at ' . $place->longurl;

                        return (new Response($content, 201))
                                      ->header('Location', $place->longurl);
                    }
                }
            }
            $content = http_build_query([
                'error' => 'invalid_token',
                'error_description' => 'The token provided is not valid or does not have the necessary scope',
            ]);

            return (new Response($content, 400))
                          ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'No OAuth token sent with request.';

        return new Response($content, 400);
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
                return new Response('Invalid token', 400);
            }
            //we have a valid token, is `syndicate-to` set?
            if ($request->input('q') === 'syndicate-to') {
                $content = http_build_query([
                    'mp-syndicate-to' => 'twitter.com/jonnybarnes',
                ]);

                return (new Response($content, 200))
                              ->header('Content-Type', 'application/x-www-form-urlencoded');
            }
            //nope, how about a geo URL?
            if (substr($request->input('q'), 0, 4) === 'geo:') {
                $geo = explode(':', $request->input('q'));
                $latlng = explode(',', $geo[1]);
                $latitude = $latlng[0];
                $longitude = $latlng[1];
                $places = Place::near($latitude, $longitude, 1000);

                return (new Response(json_encode($places), 200))
                        ->header('Content-Type', 'application/json');
            }
            //nope, just return the token
            $content = http_build_query([
                'me' => $valid->getClaim('me'),
                'scope' => $valid->getClaim('scope'),
                'client_id' => $valid->getClaim('client_id'),
            ]);

            return (new Response($content, 200))
                          ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'No OAuth token sent with request.';

        return new Response($content, 400);
    }
}
