<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\IndieAuthService;
use Illuminate\Support\Facades\Log;
use IndieAuth\Client as IndieClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;

class MicropubClientController extends Controller
{
    /**
     * The IndieAuth service container.
     */
    protected $indieAuthService;

    /**
     * Inject the dependencies.
     */
    public function __construct(
        IndieAuthService $indieAuthService = null,
        IndieClient $indieClient = null,
        GuzzleClient $guzzleClient = null
    ) {
        $this->indieAuthService = $indieAuthService ?? new IndieAuthService();
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
        $url = $request->session()->get('me');
        $syndication = $request->session()->get('syndication');
        $mediaEndpoint = $request->session()->get('media-endpoint');
        $mediaURLs = $request->session()->get('media-links');

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

        $mediaEndpoint = $request->session()->get('media-endpoint');
        if ($mediaEndpoint == null) {
            return back();
        }

        $token = $request->session()->get('token');

        $mediaURLs = [];
        foreach ($request->file('file') as $file) {
            try {
                $response = $this->guzzleClient->request('POST', $mediaEndpoint, [
                    'headers' => ['Authorization' => 'Bearer ' . $token],
                    'mulitpart' => [
                        [
                            'name' => $file->getClientOriginalName(),
                            'file' => fopen($file->path(), 'r'),
                        ],
                    ],
                ]);
            } catch (ClientException | ServerException $e) {
                continue;
            }

            $mediaURLs[] = $response->header('Location');
        }

        $request->session()->put('media-links', $mediaURLs);

        return redirect(route('micropub-client'));
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
        $domain = $request->session()->get('me');
        $token = $request->session()->get('token');

        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint(
            $domain,
            $this->indieClient
        );
        if (! $micropubEndpoint) {
            return redirect(route('micropub-client'))->with('error', 'Unable to determine micropub API endpoint');
        }

        $response = $this->postNoteRequest($request, $micropubEndpoint, $token);

        if ($response->getStatusCode() == 201) {
            $location = $response->getHeader('Location');
            if (is_array($location)) {
                return redirect($location[0]);
            }

            return redirect($location);
        }

        return redirect(route('micropub-client'))->with('error', 'Endpoint didn’t create the note.');
    }

    /**
     * Show currently stored configuration values.
     *
     * @param  Illuminate\Http\Request $request
     * @return view
     */
    public function config(Request $request)
    {
        $data['me'] = $request->session()->get('me');
        $data['token'] = $request->session()->get('token');
        $data['syndication'] = $request->session()->get('syndication') ?? 'none defined';
        $data['media-endpoint'] = $request->session()->get('media-endpoint') ?? 'none defined';

        return view('micropub.config', compact('data'));
    }

    /**
     * Query the micropub endpoint and store response in the session.
     *
     * @param  Illuminate\Http\Request $request
     * @return redirect
     */
    public function queryEndpoint(Request $request)
    {
        $domain = $request->session()->get('me');
        $token = $request->session()->get('token');
        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain);
        if ($micropubEndpoint !== null) {
            try {
                $response = $this->guzzleClient->get($micropubEndpoint, [
                    'headers' => ['Authorization' => 'Bearer ' . $token],
                    'query' => 'q=config',
                ]);
            } catch (ClientException | ServerException $e) {
                return back();
            }
            $body = (string) $response->getBody();

            $syndication = $this->parseSyndicationTargets($body);
            $request->session()->put('syndication', $syndication);

            $mediaEndpoint = $this->parseMediaEndpoint($body);
            $request->session()->put('media-endpoint', $mediaEndpoint);

            return back();
        }
    }

    /**
     * We make a request to the micropub endpoint requesting syndication targets
     * and store them in the session.
     *
     * @todo better handling of response regarding mp-syndicate-to
     *       and syndicate-to
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Redirector redirect
     */
    public function refreshSyndicationTargets(Request $request)
    {
        $domain = $request->session()->get('me');
        $token = $request->session()->get('token');
        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain, $this->indieClient);
        if (! $micropubEndpoint) {
            return redirect(route('micropub-client'))->with('error', 'Unable to determine micropub API endpoint');
        }

        try {
            $response = $this->guzzleClient->get($micropubEndpoint, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => 'syndicate-to'],
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return redirect(route('micropub-client'))->with(
                'error',
                'Bad response when refreshing syndication targets'
            );
        }
        $body = (string) $response->getBody();
        $syndication = $this->parseSyndicationTargets($body);

        $request->session()->put('syndication', $syndication);

        return redirect(route('micropub-client'));
    }

    /**
     * This method performs the actual POST request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string The Micropub endpoint to post to
     * @param  string The token to authenticate the request with
     * @return \GuzzleHttp\Response $response | \Illuminate\RedirectFactory redirect
     */
    private function postNoteRequest(
        Request $request,
        $micropubEndpoint,
        $token
    ) {
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
        $headers = [
            'Authorization' => 'Bearer ' . $token,
        ];
        try {
            $response = $this->guzzleClient->post($micropubEndpoint, [
                'multipart' => $multipart,
                'headers' => $headers,
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return redirect(route('micropub-client'))->with(
                'error',
                'There was a bad response from the micropub endpoint.'
            );
        }

        return $response;
    }

    /**
     * Create a new place.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function newPlace(Request $request)
    {
        if ($request->session()->has('token') === false) {
            return response()->json([
                'error' => true,
                'error_description' => 'No known token',
            ], 400);
        }
        $domain = $request->session()->get('me');
        $token = $request->session()->get('token');

        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain, $this->indieClient);
        if (! $micropubEndpoint) {
            return response()->json([
                'error' => true,
                'error_description' => 'Could not determine the micropub endpoint.',
            ], 400);
        }

        $place = $this->postPlaceRequest($request, $micropubEndpoint, $token);
        if ($place === false) {
            return response()->json([
                'error' => true,
                'error_description' => 'Unable to create the new place',
            ], 400);
        }

        return response()->json([
            'uri' => $place,
            'name' => $request->input('place-name'),
            'latitude' => $request->input('place-latitude'),
            'longitude' => $request->input('place-longitude'),
        ]);
    }

    /**
     * Actually make a micropub request to make a new place.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  string The Micropub endpoint to post to
     * @param  string The token to authenticate the request with
     * @param  \GuzzleHttp\Client $client
     * @return \GuzzleHttp\Response $response | \Illuminate\RedirectFactory redirect
     */
    private function postPlaceRequest(
        Request $request,
        $micropubEndpoint,
        $token
    ) {
        $formParams = [
            'h' => 'card',
            'name' => $request->input('place-name'),
            'description' => $request->input('place-description'),
            'geo' => 'geo:' . $request->input('place-latitude') . ',' . $request->input('place-longitude'),
        ];
        $headers = [
            'Authorization' => 'Bearer ' . $token,
        ];
        try {
            $response = $this->guzzleClient->request('POST', $micropubEndpoint, [
                'form_params' => $formParams,
                'headers' => $headers,
            ]);
        } catch (ClientException $e) {
            return false;
        }
        if ($response->getStatusCode() == 201) {
            return $response->getHeader('Location')[0];
        }

        return false;
    }

    /**
     * Make a request to the micropub endpoint requesting any nearby places.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function nearbyPlaces(Request $request)
    {
        if ($request->session()->has('token') === false) {
            return response()->json([
                'error' => true,
                'error_description' => 'No known token',
            ], 400);
        }
        $domain = $request->session()->get('me');
        $token = $request->session()->get('token');

        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain, $this->indieClient);

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
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => $query],
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            Log::info($e->getResponse()->getBody());

            return response()->json([
                'error' => true,
                'error_description' => 'The endpoint ' . $micropubEndpoint . ' returned a non-good response',
            ], 400);
        }

        return response($response->getBody(), 200)
                ->header('Content-Type', 'application/json');
    }

    /**
     * Parse the syndication targets retreived from a cookie, to a form that can
     * be used in a view.
     *
     * @param  string $syndicationTargets
     * @return array|null
     */
    private function parseSyndicationTargets($syndicationTargets = null)
    {
        if ($syndicationTargets === null) {
            return;
        }
        $syndicateTo = [];
        $data = json_decode($syndicationTargets, true);
        if (array_key_exists('syndicate-to', $data)) {
            foreach ($data['syndicate-to'] as $syn) {
                $syndicateTo[] = [
                    'target' => $syn['uid'],
                    'name' => $syn['name'],
                ];
            }
        }
        if (count($syndicateTo) > 0) {
            return $syndicateTo;
        }
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
