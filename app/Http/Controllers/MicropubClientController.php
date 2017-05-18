<?php

namespace App\Http\Controllers;

use App\IndieWebUser;
use IndieAuth\Client as IndieClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\{Request, Response};
use GuzzleHttp\Exception\{ClientException, ServerException};

class MicropubClientController extends Controller
{
    /**
     * Inject the dependencies.
     */
    public function __construct(
        IndieClient $indieClient = null,
        GuzzleClient $guzzleClient = null
    ) {
        $this->guzzleClient = $guzzleClient ?? new GuzzleClient();
        $this->indieClient = $indieClient ?? new IndieClient();
    }

    /**
     * Display the new notes form.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function create(Request $request)
    {
        //initiate varaibles
        $indiewebUser = null;
        $syndication = null;
        $mediaEndpoint = null;
        $mediaURLs = null;
        $url = $request->session()->get('me');
        if ($url) {
            $indiewebUser = IndieWebUser::where('me', $url)->first();
        }
        if ($indiewebUser) {
            $syndication = $this->parseSyndicationTargets($indiewebUser->syndication);
            $mediaEndpoint = $indiewebUser->mediaEndpoint ?? null;
            $mediaURLs = $request->session()->get('media-links');
        }

        return view('micropub.create', compact('url', 'syndication', 'mediaEndpoint', 'mediaURLs'));
    }

    /**
     * Process an upload to the media endpoint.
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function processMedia(Request $request)
    {
        if ($request->hasFile('file') == false) {
            return back();
        }

        $user = IndieWebUser::where('me', $request->session()->get('me'))->firstOrFail();
        if ($user->mediaEndpoint == null || $user->token == null) {
            return back();
        }

        $mediaURLs = [];
        foreach ($request->file('file') as $file) {
            try {
                $response = $this->guzzleClient->request('POST', $user->mediaEndpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $user->token,
                    ],
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => fopen($file->path(), 'r'),
                            'filename' => $file->getClientOriginalName(),
                        ],
                    ],
                ]);
            } catch (ClientException | ServerException $e) {
                continue;
            }

            $mediaURLs[] = $response->getHeader('Location')[0];
        }

        $storedMediaURLs = $request->session()->get('media-links') ?? [];
        $mediaURLsToSave = array_merge($storedMediaURLs, $mediaURLs);
        $request->session()->put('media-links', $mediaURLsToSave);

        return redirect()->route('micropub-client');
    }

    public function clearLinks(Request $request)
    {
        $request->session()->forget('media-links');

        return redirect(route('micropub-client'));
    }

    /**
     * Post the notes content to the relavent micropub API endpoint.
     *
     * @todo   make sure this works with multiple syndication targets
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $url = normalize_url($request->session()->get('me'));
        $user = IndieWebUser::where('me', $url)->firstOrFail();

        if ($user->token == null) {
            return redirect()->route('micropub-client')->with('error', 'You haven’t requested a token yet');
        }

        $micropubEndpoint = $this->indieClient->discoverMicropubEndpoint($url);
        if (! $micropubEndpoint) {
            return redirect()->route('micropub-client')->with('error', 'Unable to determine micropub API endpoint');
        }

        $headers = [
            'Authorization' => 'Bearer ' . $user->token,
        ];

        if ($user->syntax == 'html') {
            $multipart = [
                [
                    'name' => 'h',
                    'contents' => 'entry',
                ],
                [
                    'name' => 'content',
                    'contents' => $request->input('content'),
                ],
            ];
            if ($request->hasFile('photo')) {
                $photos = $request->file('photo');
                foreach ($photos as $photo) {
                    $multipart[] = [
                        'name' => 'photo[]',
                        'contents' => fopen($photo->path(), 'r'),
                        'filename' => $photo->getClientOriginalName(),
                    ];
                }
            }
            if ($request->input('in-reply-to') != '') {
                $multipart[] = [
                    'name' => 'in-reply-to',
                    'contents' => $request->input('in-reply-to'),
                ];
            }
            if ($request->input('mp-syndicate-to')) {
                foreach ($request->input('mp-syndicate-to') as $syn) {
                    $multipart[] = [
                        'name' => 'mp-syndicate-to[]',
                        'contents' => $syn,
                    ];
                }
            }
            if ($request->input('location')) {
                if ($request->input('location') !== 'no-location') {
                    $multipart[] = [
                        'name' => 'location',
                        'contents' => $request->input('location'),
                    ];
                }
            }
            if ($request->input('media')) {
                foreach ($request->input('media') as $media) {
                    $multipart[] = [
                        'name' => 'photo[]',
                        'contents' => $media,
                    ];
                }
            }
            try {
                $response = $this->guzzleClient->post($micropubEndpoint, [
                    'multipart' => $multipart,
                    'headers' => $headers,
                ]);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return redirect()->route('micropub-client')->with(
                    'error',
                    'There was a bad response from the micropub endpoint.'
                );
            }

            if ($response->getStatusCode() == 201) {
                $request->session()->forget('media-links');
                $location = $response->getHeader('Location');
                if (is_array($location)) {
                    return redirect($location[0]);
                }

                return redirect($location);
            }
        }

        if ($user->syntax == 'json') {
            $json = [];
            $json['type'] = ['h-entry'];
            $json['properties'] = ['content' => [$request->input('content')]];

            if ($request->input('in-reply-to') != '') {
                $json['properties']['in-reply-to'] = [$request->input('in-reply-to')];
            }
            if ($request->input('mp-syndicate-to')) {
                foreach ($request->input('mp-syndicate-to') as $syn) {
                    $json['properties']['mp-syndicate-to'] = [$syn];
                }
            }
            if ($request->input('location')) {
                if ($request->input('location') !== 'no-location') {
                    $json['properties']['location'] = [$request->input('location')];
                }
            }
            if ($request->input('media')) {
                $json['properties']['photo'] = [];
                foreach ($request->input('media') as $media) {
                    $json['properties']['photo'][] = $media;
                }
            }

            try {
                $response = $this->guzzleClient->post($micropubEndpoint, [
                    'json' => $json,
                    'headers' => $headers,
                ]);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return redirect()->route('micropub-client')->with(
                    'error',
                    'There was a bad response from the micropub endpoint.'
                );
            }

            if ($response->getStatusCode() == 201) {
                $request->session()->forget('media-links');
                $location = $response->getHeader('Location');
                if (is_array($location)) {
                    return redirect($location[0]);
                }

                return redirect($location);
            }
        }

        return redirect()->route('micropub-client')->with('error', 'Endpoint didn’t create the note.');
    }

    /**
     * Show currently stored configuration values.
     *
     * @param  Illuminate\Http\Request $request
     * @return view
     */
    public function config(Request $request)
    {
        //default values
        $data = [
            'me' => '',
            'token' => 'none',
            'syndication' => 'none defined',
            'media-endpoint' => 'none defined',
            'syntax' => 'html',
        ];
        if ($request->session()->has('me')) {
            $data['me'] = normalize_url($request->session()->get('me'));
            $user = IndieWebUser::where('me', $request->session()->get('me'))->first();
            $data['token'] = $user->token ?? 'none defined';
            $data['syndication'] = $user->syndication ?? 'none defined';
            $data['media-endpoint'] = $user->mediaEndpoint ?? 'none defined';
            $data['syntax'] = $user->syntax;
        }

        return view('micropub.config', compact('data'));
    }

    /**
     * Get a new token.
     *
     * @param  Illuminate\Http\Request $request
     * @return view
     */
    public function getNewToken(Request $request)
    {
        if ($request->session()->has('me')) {
            $url = normalize_url($request->session()->get('me'));
            $authozationEndpoint = $this->indieClient->discoverAuthorizationEndpoint($url);
            if ($authozationEndpoint) {
                $state = bin2hex(random_bytes(16));
                $request->session()->put('state', $state);
                $authorizationURL = $this->indieClient->buildAuthorizationURL(
                    $authozationEndpoint,
                    $url,
                    route('micropub-client-get-new-token-callback'), // redirect_uri
                    route('micropub-client'), //client_id
                    $state,
                    'create update' // scope needs to be a setting
                );

                return redirect($authorizationURL);
            }

            return redirect()->route('micropub-config')->with('error', 'Unable to find authorisation endpoint');
        }

        return redirect()->route('micropub-config')->with('error', 'You aren’t logged in');
    }

    /**
     * The callback for getting a token.
     */
    public function getNewTokenCallback(Request $request)
    {
        if ($request->input('state') !== $request->session()->get('state')) {
            return redirect()->route('micropub-config')->with('error', 'The <code>state</code> didn’t match.');
        }
        $tokenEndpoint = $this->indieClient->discoverTokenEndpoint(normalize_url($request->input('me')));
        if ($tokenEndpoint) {
            $token = $this->indieClient->getAccessToken(
                $tokenEndpoint,
                $request->input('code'),
                $request->input('me'),
                route('micropub-client-get-new-token-callback'), // redirect_uri
                route('micropub-client'), // client_id
                $request->input('state')
            );
            if (array_key_exists('access_token', $token)) {
                $url = normalize_url($token['me']);
                $user = IndieWebUser::where('me', $url)->firstOrFail();
                $user->token = $token['access_token'];
                $user->save();

                return redirect()->route('micropub-config');
            }

            return redirect()->route('micropub-config')->with('error', 'Error getting token from the endpoint');
        }

        return redirect()->route('micropub-config')->with('error', 'Unable to find token endpoint');
    }

    /**
     * Query the micropub endpoint and store response.
     *
     * @param  Illuminate\Http\Request $request
     * @return redirect
     */
    public function queryEndpoint(Request $request)
    {
        $url = normalize_url($request->session()->get('me'));
        $user = IndieWebUser::where('me', $url)->firstOrFail();
        $token = $user->token;
        $micropubEndpoint = $this->indieClient->discoverMicropubEndpoint($url);
        if ($micropubEndpoint) {
            try {
                $response = $this->guzzleClient->get($micropubEndpoint, [
                    'headers' => ['Authorization' => 'Bearer ' . $token],
                    'query' => 'q=config',
                ]);
            } catch (ClientException | ServerException $e) {
                return back();
            }
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (array_key_exists('syndicate-to', $data)) {
                $user->syndication = json_encode($data['syndicate-to']);
            }

            if (array_key_exists('media-endpoint', $data)) {
                $user->mediaEndpoint = $data['media-endpoint'];
            }
            $user->save();

            return back();
        }
    }

    /**
     * Update the syntax setting.
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Http\RedirectResponse
     * @todo validate input
     */
    public function updateSyntax(Request $request)
    {
        $user = IndieWebUser::where('me', $request->session()->get('me'))->firstOrFail();
        $user->syntax = $request->syntax;
        $user->save();

        return redirect()->route('micropub-config');
    }

    /**
     * Create a new place.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function newPlace(Request $request)
    {
        $url = normalize_url($request->session()->get('me'));
        $user = IndieWebUser::where('me', $url)->firstOrFail();

        if ($user->token === null) {
            return response()->json([
                'error' => true,
                'error_description' => 'No known token',
            ], 400);
        }

        $micropubEndpoint = $this->indieClient->discoverMicropubEndpoint($url);
        if (! $micropubEndpoint) {
            return response()->json([
                'error' => true,
                'error_description' => 'Could not determine the micropub endpoint.',
            ], 400);
        }

        $formParams = [
            'h' => 'card',
            'name' => $request->input('place-name'),
            'description' => $request->input('place-description'),
            'geo' => 'geo:' . $request->input('place-latitude') . ',' . $request->input('place-longitude'),
        ];
        $headers = [
            'Authorization' => 'Bearer ' . $user->token,
        ];
        try {
            $response = $this->guzzleClient->request('POST', $micropubEndpoint, [
                'form_params' => $formParams,
                'headers' => $headers,
            ]);
        } catch (ClientException $e) {
            return response()->json([
                'error' => true,
                'error_description' => 'Unable to create the new place',
            ], 400);
        }
        $place = $response->getHeader('Location')[0];

        return response()->json([
            'uri' => $place,
            'name' => $request->input('place-name'),
            'latitude' => $request->input('place-latitude'),
            'longitude' => $request->input('place-longitude'),
        ]);
    }

    /**
     * Make a request to the micropub endpoint requesting any nearby places.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function nearbyPlaces(Request $request)
    {
        $url = normalize_url($request->session()->get('me'));
        $user = IndieWebUser::where('me', $url)->firstOrFail();

        if ($user->token === null) {
            return response()->json([
                'error' => true,
                'error_description' => 'No known token',
            ], 400);
        }

        $micropubEndpoint = $this->indieClient->discoverMicropubEndpoint($url);

        if (! $micropubEndpoint) {
            return response()->json([
                'error' => true,
                'error_description' => 'No known endpoint',
            ], 400);
        }

        try {
            $query = 'geo:' . $request->input('latitude') . ',' . $request->input('longitude');
            if ($request->input('u') !== null) {
                $query .= ';u=' . $request->input('u');
            }
            $response = $this->guzzleClient->get($micropubEndpoint, [
                'headers' => ['Authorization' => 'Bearer ' . $user->token],
                'query' => ['q' => $query],
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return response()->json([
                'error' => true,
                'error_description' => 'The endpoint ' . $micropubEndpoint . ' returned a non-good response',
            ], 400);
        }

        return response($response->getBody(), 200)
                ->header('Content-Type', 'application/json');
    }

    /**
     * Parse the syndication targets JSON into a an array.
     *
     * @param  string|null
     * @return array|null
     */
    private function parseSyndicationTargets($syndicationTargets = null)
    {
        if ($syndicationTargets === null || $syndicationTargets === '') {
            return;
        }
        $syndicateTo = [];
        $data = json_decode($syndicationTargets, true);
        if (array_key_exists('uid', $data)) {
            $syndicateTo[] = [
                'target' => $data['uid'],
                'name' => $data['name'],
            ];
        }
        foreach ($data as $syn) {
            if (array_key_exists('uid', $syn)) {
                $syndicateTo[] = [
                    'target' => $syn['uid'],
                    'name' => $syn['name'],
                ];
            }
        }

        return $syndicateTo;
    }

    /**
     * Parse the media-endpoint retrieved from querying a micropub endpoint.
     *
     * @param  string|null
     * @return string
     */
    private function parseMediaEndpoint($queryResponse = null)
    {
        if ($queryResponse === null) {
            return;
        }

        $data = json_decode($queryResponse, true);
        if (array_key_exists('media-endpoint', $data)) {
            return $data['media-endpoint'];
        }
    }
}
