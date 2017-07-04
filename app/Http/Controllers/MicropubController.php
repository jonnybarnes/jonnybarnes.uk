<?php

namespace App\Http\Controllers;

use Monolog\Logger;
use Ramsey\Uuid\Uuid;
use App\{Media, Note, Place};
use Monolog\Handler\StreamHandler;
use Illuminate\Http\{Request, Response};
use App\Exceptions\InvalidTokenException;
use Phaza\LaravelPostgis\Geometries\Point;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use App\Services\{NoteService, PlaceService, TokenService};

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
        try {
            $tokenData = $this->tokenService->validateToken($request->bearerToken());
        } catch (InvalidTokenException $e) {
            return response()->json([
                'response' => 'error',
                'error' => 'invalid_token',
                'error_description' => 'The provided token did not pass validation',
            ], 400);
        }
        // Log the request
        $logger = new Logger('micropub');
        $logger->pushHandler(new StreamHandler(storage_path('logs/micropub.log')), Logger::DEBUG);
        $logger->debug('MicropubLog', $request->all());
        if ($tokenData->hasClaim('scope')) {
            if (($request->input('h') == 'entry') || ($request->input('type.0') == 'h-entry')) {
                if (stristr($tokenData->getClaim('scope'), 'create') === false) {
                    return $this->returnInsufficientScopeResponse();
                }
                $data = [];
                $data['client-id'] = $tokenData->getClaim('client_id');
                if ($request->header('Content-Type') == 'application/json') {
                    if (is_string($request->input('properties.content.0'))) {
                        $data['content'] = $request->input('properties.content.0'); //plaintext content
                    }
                    if (is_array($request->input('properties.content.0'))
                        && array_key_exists('html', $request->input('properties.content.0'))
                    ) {
                        $data['content'] = $request->input('properties.content.0.html');
                    }
                    $data['in-reply-to'] = $request->input('properties.in-reply-to.0');
                    // check location is geo: string
                    if (is_string($request->input('properties.location.0'))) {
                        $data['location'] = $request->input('properties.location.0');
                    }
                    // check location is h-card
                    if (is_array($request->input('properties.location.0'))) {
                        if ($request->input('properties.location.0.type.0' === 'h-card')) {
                            try {
                                $place = $this->placeService->createPlaceFromCheckin($request->input('properties.location.0'));
                                $data['checkin'] = $place->longurl;
                            } catch (\Exception $e) {
                                //
                            }
                        }
                    }
                    $data['published'] = $request->input('properties.published.0');
                    //create checkin place
                    if (array_key_exists('checkin', $request->input('properties'))) {
                        $data['swarm-url'] = $request->input('properties.syndication.0');
                        try {
                            $place = $this->placeService->createPlaceFromCheckin($request->input('properties.checkin.0'));
                            $data['checkin'] = $place->longurl;
                        } catch (\Exception $e) {
                            $data['checkin'] = null;
                            $data['swarm-url'] = null;
                        }
                    }
                } else {
                    $data['content'] = $request->input('content');
                    $data['in-reply-to'] = $request->input('in-reply-to');
                    $data['location'] = $request->input('location');
                    $data['published'] = $request->input('published');
                }
                $data['syndicate'] = [];
                $targets = array_pluck(config('syndication.targets'), 'uid', 'service.name');
                $mpSyndicateTo = null;
                if ($request->has('mp-syndicate-to')) {
                    $mpSyndicateTo = $request->input('mp-syndicate-to');
                }
                if ($request->has('properties.mp-syndicate-to')) {
                    $mpSyndicateTo = $request->input('properties.mp-syndicate-to');
                }
                if (is_string($mpSyndicateTo)) {
                    $service = array_search($mpSyndicateTo, $targets);
                    if ($service == 'Twitter') {
                        $data['syndicate'][] = 'twitter';
                    }
                    if ($service == 'Facebook') {
                        $data['syndicate'][] = 'facebook';
                    }
                }
                if (is_array($mpSyndicateTo)) {
                    foreach ($mpSyndicateTo as $uid) {
                        $service = array_search($uid, $targets);
                        if ($service == 'Twitter') {
                            $data['syndicate'][] = 'twitter';
                        }
                        if ($service == 'Facebook') {
                            $data['syndicate'][] = 'facebook';
                        }
                    }
                }
                $data['photo'] = [];
                $photos = null;
                if ($request->has('photo')) {
                    $photos = $request->input('photo');
                }
                if ($request->has('properties.photo')) {
                    $photos = $request->input('properties.photo');
                }
                if ($photos !== null) {
                    foreach ($photos as $photo) {
                        if (is_string($photo)) {
                            //only supporting media URLs for now
                            $data['photo'][] = $photo;
                        }
                    }
                    if (starts_with($request->input('properties.syndication.0'), 'https://www.instagram.com')) {
                        $data['instagram-url'] = $request->input('properties.syndication.0');
                    }
                }
                try {
                    $note = $this->noteService->createNote($data);
                } catch (\Exception $exception) {
                    return response()->json(['error' => true], 400);
                }

                return response()->json([
                    'response' => 'created',
                    'location' => $note->longurl,
                ], 201)->header('Location', $note->longurl);
            }
            if ($request->input('h') == 'card' || $request->input('type')[0] == 'h-card') {
                if (stristr($tokenData->getClaim('scope'), 'create') === false) {
                    return $this->returnInsufficientScopeResponse();
                }
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
                } catch (\Exception $exception) {
                    return response()->json(['error' => true], 400);
                }

                return response()->json([
                    'response' => 'created',
                    'location' => $place->longurl,
                ], 201)->header('Location', $place->longurl);
            }
            if ($request->input('action') == 'update') {
                if (stristr($tokenData->getClaim('scope'), 'update') === false) {
                    return $this->returnInsufficientScopeResponse();
                }
                $urlPath = parse_url($request->input('url'), PHP_URL_PATH);
                //is it a note we are updating?
                if (mb_substr($urlPath, 1, 5) === 'notes') {
                    try {
                        $note = Note::nb60(basename($urlPath))->first();
                    } catch (\Exception $exception) {
                        return response()->json([
                            'error' => 'invalid_request',
                            'error_description' => 'No known note with given ID',
                        ]);
                    }
                    //got the note, are we dealing with a “replace” request?
                    if ($request->has('replace')) {
                        foreach ($request->input('replace') as $property => $value) {
                            if ($property == 'content') {
                                $note->note = $value[0];
                            }
                            if ($property == 'syndication') {
                                foreach ($value as $syndicationURL) {
                                    if (starts_with($syndicationURL, 'https://www.facebook.com')) {
                                        $note->facebook_url = $syndicationURL;
                                    }
                                    if (starts_with($syndicationURL, 'https://www.swarmapp.com')) {
                                        $note->swarm_url = $syndicationURL;
                                    }
                                    if (starts_with($syndicationURL, 'https://twitter.com')) {
                                        $note->tweet_id = basename(parse_url($syndicationURL, PHP_URL_PATH));
                                    }
                                }
                            }
                        }
                        $note->save();

                        return response()->json([
                            'response' => 'updated',
                        ]);
                    }
                    //how about “add”
                    if ($request->has('add')) {
                        foreach ($request->input('add') as $property => $value) {
                            if ($property == 'syndication') {
                                foreach ($value as $syndicationURL) {
                                    if (starts_with($syndicationURL, 'https://www.facebook.com')) {
                                        $note->facebook_url = $syndicationURL;
                                    }
                                    if (starts_with($syndicationURL, 'https://www.swarmapp.com')) {
                                        $note->swarm_url = $syndicationURL;
                                    }
                                    if (starts_with($syndicationURL, 'https://twitter.com')) {
                                        $note->tweet_id = basename(parse_url($syndicationURL, PHP_URL_PATH));
                                    }
                                }
                            }
                            if ($property == 'photo') {
                                foreach ($value as $photoURL) {
                                    if (start_with($photo, 'https://')) {
                                        $media = new Media();
                                        $media->path = $photoURL;
                                        $media->type = 'image';
                                        $media->save();
                                        $note->media()->save($media);
                                    }
                                }
                            }
                        }
                        $note->save();

                        return response()->json([
                            'response' => 'updated',
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'response' => 'error',
            'error' => 'forbidden',
            'error_description' => 'The token has no scopes',
        ], 403);
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
        try {
            $tokenData = $this->tokenService->validateToken($request->bearerToken());
        } catch (InvalidTokenException $e) {
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

        //nope, how about a config query?
        if ($request->input('q') == 'config') {
            return response()->json([
                'syndicate-to' => config('syndication.targets'),
                'media-endpoint' => route('media-endpoint'),
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
            $places = Place::near(new Point($matches[0][0], $matches[0][1]))->get();
            foreach ($places as $place) {
                $place->uri = config('app.url') . '/places/' . $place->slug;
            }

            return response()->json([
                'response' => 'places',
                'places' => $places,
            ]);
        }

        //nope, just return the token
        return response()->json([
            'response' => 'token',
            'token' => [
                'me' => $tokenData->getClaim('me'),
                'scope' => $tokenData->getClaim('scope'),
                'client_id' => $tokenData->getClaim('client_id'),
            ],
        ]);
    }

    /**
     * Process a media item posted to the media endpoint.
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function media(Request $request)
    {
        try {
            $tokenData = $this->tokenService->validateToken($request->bearerToken());
        } catch (InvalidTokenException $e) {
            return response()->json([
                'response' => 'error',
                'error' => 'invalid_token',
                'error_description' => 'The provided token did not pass validation',
            ], 400);
        }

        //check post scope
        if ($tokenData->hasClaim('scope')) {
            if (stristr($tokenData->getClaim('scope'), 'create') === false) {
                return $this->returnInsufficientScopeResponse();
            }
            //check media valid
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $type = $this->getFileTypeFromMimeType($request->file('file')->getMimeType());
                try {
                    $filename = Uuid::uuid4() . '.' . $request->file('file')->extension();
                } catch (UnsatisfiedDependencyException $e) {
                    return response()->json([
                        'response' => 'error',
                        'error' => 'internal_server_error',
                        'error_description' => 'A problem occured handling your request',
                    ], 500);
                }
                try {
                    $path = $request->file('file')->storeAs('media', $filename, 's3');
                } catch (Exception $e) { // which exception?
                    return response()->json([
                        'response' => 'error',
                        'error' => 'service_unavailable',
                        'error_description' => 'Unable to save media to S3',
                    ], 503);
                }
                $media = new Media();
                $media->token = $request->bearerToken();
                $media->path = $path;
                $media->type = $type;
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
            'error' => 'invalid_request',
            'error_description' => 'The provided token has no scopes',
        ], 400);
    }

    /**
     * Get the file type from the mimetype of the uploaded file.
     *
     * @param  string The mimetype
     * @return string The type
     */
    private function getFileTypeFromMimeType($mimetype)
    {
        //try known images
        $imageMimeTypes = [
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/svg+xml',
            'image/tiff',
            'image/webp',
        ];
        if (in_array($mimetype, $imageMimeTypes)) {
            return 'image';
        }
        //try known video
        $videoMimeTypes = [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/webm',
        ];
        if (in_array($mimetype, $videoMimeTypes)) {
            return 'video';
        }
        //try known audio types
        $audioMimeTypes = [
            'audio/midi',
            'audio/mpeg',
            'audio/ogg',
            'audio/x-m4a',
        ];
        if (in_array($mimetype, $audioMimeTypes)) {
            return 'audio';
        }

        return 'download';
    }

    private function returnInsufficientScopeResponse()
    {
        return response()->json([
            'response' => 'error',
            'error' => 'insufficient_scope',
            'error_description' => 'The token’s scope does not have the necessary requirements.',
        ], 401);
    }
}
