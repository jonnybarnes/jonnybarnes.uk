<?php

namespace App\Http\Controllers;

use IndieAuth\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\TokenService;
use Illuminate\Cookie\CookieJar;
use App\Services\IndieAuthService;

class IndieAuthController extends Controller
{
    /**
     * This service isolates the IndieAuth Client code.
     */
    protected $indieAuthService;

    /**
     * The IndieAuth Client implementation.
     */
    protected $client;

    /**
     * The Token handling service.
     */
    protected $tokenService;

    /**
     * Inject the dependencies.
     *
     * @param  \App\Services\IndieAuthService $indieAuthService
     * @param  \IndieAuth\Client $client
     * @return void
     */
    public function __construct(
        IndieAuthService $indieAuthService = null,
        Client $client = null,
        TokenService $tokenService = null
    ) {
        $this->indieAuthService = $indieAuthService ?? new IndieAuthService();
        $this->client = $client ?? new Client();
        $this->tokenService = $tokenService ?? new TokenService();
    }

    /**
     * Begin the indie auth process. This method ties in to the login page
     * from our micropub client. Here we then query the user’s homepage
     * for their authorisation endpoint, and redirect them there with a
     * unique secure state value.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function beginauth(Request $request)
    {
        $authorizationEndpoint = $this->indieAuthService->getAuthorizationEndpoint(
            $request->input('me'),
            $this->client
        );
        if ($authorizationEndpoint) {
            $authorizationURL = $this->indieAuthService->buildAuthorizationURL(
                $authorizationEndpoint,
                $request->input('me'),
                $this->client
            );
            if ($authorizationURL) {
                return redirect($authorizationURL);
            }
        }

        return redirect('/notes/new')->withErrors('Unable to determine authorisation endpoint', 'indieauth');
    }

    /**
     * Once they have verified themselves through the authorisation endpint
     * the next step is retreiveing a token from the token endpoint.
     *
     * @param  \Illuminate\Http\Rrequest $request
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function indieauth(Request $request)
    {
        if ($request->session()->get('state') != $request->input('state')) {
            return redirect('/notes/new')->withErrors(
                'Invalid <code>state</code> value returned from indieauth server',
                'indieauth'
            );
        }
        $tokenEndpoint = $this->indieAuthService->getTokenEndpoint($request->input('me'), $this->client);
        $redirectURL = config('app.url') . '/indieauth';
        $clientId = config('app.url') . '/notes/new';
        $data = [
            'endpoint' => $tokenEndpoint,
            'code' => $request->input('code'),
            'me' => $request->input('me'),
            'redirect_url' => $redirectURL,
            'client_id' => $clientId,
            'state' => $request->input('state'),
        ];
        $token = $this->indieAuthService->getAccessToken($data, $this->client);

        if (array_key_exists('access_token', $token)) {
            $request->session()->put('me', $token['me']);
            $request->session()->put('token', $token['access_token']);

            return redirect('/notes/new');
        }

        return redirect('/notes/new')->withErrors('Unable to get a token from the endpoint', 'indieauth');
    }

    /**
     * If the user has auth’d via IndieAuth, issue a valid token.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function tokenEndpoint(Request $request)
    {
        $authData = [
            'code' => $request->input('code'),
            'me' => $request->input('me'),
            'redirect_url' => $request->input('redirect_uri'),
            'client_id' => $request->input('client_id'),
            'state' => $request->input('state'),
        ];
        $auth = $this->indieAuthService->verifyIndieAuthCode($authData, $this->client);
        if (array_key_exists('me', $auth)) {
            $scope = $auth['scope'] ?? '';
            $tokenData = [
                'me' => $request->input('me'),
                'client_id' => $request->input('client_id'),
                'scope' => $auth['scope'],
            ];
            $token = $this->tokenService->getNewToken($tokenData);
            $content = http_build_query([
                'me' => $request->input('me'),
                'scope' => $scope,
                'access_token' => $token,
            ]);

            return (new Response($content, 200))
                           ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'There was an error verifying the authorisation code.';

        return new Response($content, 400);
    }

    /**
     * Log out the user, flush an session data, and overwrite any cookie data.
     *
     * @param  \Illuminate\Cookie\CookieJar $cookie
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function indieauthLogout(Request $request, CookieJar $cookie)
    {
        $request->session()->flush();
        $cookie->queue('me', 'loggedout', 5);

        return redirect('/notes/new');
    }
}
