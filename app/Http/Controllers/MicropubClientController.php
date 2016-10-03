<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\IndieAuthService;
use IndieAuth\Client as IndieClient;
use GuzzleHttp\Client as GuzzleClient;

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
    public function newNotePage(Request $request)
    {
        $url = $request->session()->get('me');
        $syndication = $request->session()->get('syndication');

        return view('micropubnewnotepage', [
            'url' => $url,
            'syndication' => $syndication,
        ]);
    }

    /**
     * Post the notes content to the relavent micropub API endpoint.
     *
     * @todo   make sure this works with multiple syndication targets
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function postNewNote(Request $request)
    {
        $domain = $request->session()->get('me');
        $token = $request->session()->get('token');

        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint(
            $domain,
            $this->indieClient
        );
        if (! $micropubEndpoint) {
            return redirect('notes/new')->withErrors('Unable to determine micropub API endpoint', 'endpoint');
        }

        $response = $this->postNoteRequest($request, $micropubEndpoint, $token);

        if ($response->getStatusCode() == 201) {
            $location = $response->getHeader('Location');
            if (is_array($location)) {
                return redirect($location[0]);
            }

            return redirect($location);
        }

        return redirect('notes/new')->withErrors('Endpoint didn’t create the note.', 'endpoint');
    }

    /**
     * We make a request to the micropub endpoint requesting syndication targets
     * and store them in the session.
     *
     * @todo better handling of response regarding mp-syndicate-to
     *       and syndicate-to
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \IndieAuth\Client $indieClient
     * @param  \GuzzleHttp\Client $guzzleClient
     * @return \Illuminate\Routing\Redirector redirect
     */
    public function refreshSyndicationTargets(Request $request)
    {
        $domain = $request->session()->get('me');
        $token = $request->session()->get('token');
        $micropubEndpoint = $this->indieAuthService->discoverMicropubEndpoint($domain, $this->indieClient);

        if (! $micropubEndpoint) {
            return redirect('notes/new')->withErrors('Unable to determine micropub API endpoint', 'endpoint');
        }

        try {
            $response = $this->guzzleClient->get($micropubEndpoint, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => 'syndicate-to'],
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return redirect('notes/new')->withErrors('Bad response when refreshing syndication targets', 'endpoint');
        }
        $body = (string) $response->getBody();
        $syndication = $this->parseSyndicationTargets($body);

        $request->session()->put('syndication', $syndication);

        return redirect('notes/new');
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
                $filename = $photo->getClientOriginalName();
                $photo->move(storage_path() . '/media-tmp', $filename);
                $multipart[] = [
                    'name' => 'photo[]',
                    'contents' => fopen(storage_path() . '/media-tmp/' . $filename, 'r'),
                ];
            }
        }
        if ($request->input('in-reply-to') != '') {
            $multipart[] = [
                'name' => 'in-reply-to',
                'contents' => $request->input('reply-to'),
            ];
        }
        if ($request->input('syndicate-to')) {
            foreach ($request->input('syndicate-to') as $syn) {
                $multipart[] = [
                    'name' => 'syndicate-to',
                    'contents' => $syn,
                ];
            }
        }
        if ($request->input('confirmlocation')) {
            $latLng = $request->input('location');
            $geoURL = 'geo:' . str_replace(' ', '', $latLng);
            $multipart[] = [
                'name' => 'location',
                'contents' => $geoURL,
            ];
            if ($request->input('address') != '') {
                $multipart[] = [
                    'name' => 'place_name',
                    'contents' => $request->input('address'),
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
            return redirect('notes/new')
                ->withErrors('There was a bad response from the micropub endpoint.', 'endpoint');
        }

        return $response;
    }

    /**
     * Create a new place.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function postNewPlace(Request $request)
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
            'url' => $place,
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
     * @param  string $latitude
     * @param  string $longitude
     * @return \Illuminate\Http\Response
     */
    public function nearbyPlaces(
        Request $request,
        $latitude,
        $longitude
    ) {
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
            $query = 'geo:' . $latitude . ',' . $longitude;
            if ($request->input('u') !== null) {
                $query .= ';u=' . $request->input('u');
            }
            $response = $this->guzzleClient->get($micropubEndpoint, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => $query],
            ]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return response()->json([
                'error' => true,
                'error_description' => 'The endpoint returned a non-good response',
                'error_stack' => $e->getMessage()
            ], 400);
        }

        return (new Response($response->getBody(), 200))
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
}
