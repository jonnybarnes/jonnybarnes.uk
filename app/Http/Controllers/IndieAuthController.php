<?php

namespace App\Http\Controllers;

use App\IndieWebUser;
use IndieAuth\Client;
use Illuminate\Http\Request;
use App\Services\TokenService;

class IndieAuthController extends Controller
{
    /**
     * The IndieAuth Client.
     */
    protected $client;

    /**
     * The Token handling service.
     */
    protected $tokenService;

    /**
     * Inject the dependencies.
     *
     * @param  \IndieAuth\Client $client
     * @param  \App\Services\TokenService $tokenService
     * @return void
     */
    public function __construct(
        Client $client = null,
        TokenService $tokenService = null
    ) {
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
    public function start(Request $request)
    {
        $url = normalize_url($request->input('me'));
        $authorizationEndpoint = $this->client->discoverAuthorizationEndpoint($url);
        if ($authorizationEndpoint != null) {
            $state = bin2hex(openssl_random_pseudo_bytes(16));
            session(['state' => $state]);
            $authorizationURL = $this->client->buildAuthorizationURL(
                $authorizationEndpoint,
                $url,
                route('indieauth-callback'), //redirect_uri
                route('micropub-client'), //client_id
                $state
            );
            if ($authorizationURL) {
                return redirect($authorizationURL);
            }

            return redirect(route('micropub-client'))->with('error', 'Error building authorization URL');
        }

        return redirect(route('micropub-client'))->with('error', 'Unable to determine authorisation endpoint');
    }

    /**
     * Once they have verified themselves through the authorisation endpoint
     * the next step is register/login the user.
     *
     * @param  \Illuminate\Http\Rrequest $request
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function callback(Request $request)
    {
        if ($request->session()->get('state') != $request->input('state')) {
            return redirect(route('micropub-client'))->with(
                'error',
                'Invalid <code>state</code> value returned from indieauth server'
            );
        }

        $url = normalize_url($request->input('me'));
        $indiewebUser = IndieWebUser::firstOrCreate(['me' => $url]);
        $request->session()->put(['me' => $url]);

        return redirect(route('micropub-client'));
    }

/*
        $tokenEndpoint = $this->indieAuthService->getTokenEndpoint($request->input('me'));
        if ($tokenEndpoint === false) {
            return redirect(route('micropub-client'))->with(
                'error',
                'Unable to determine token endpoint'
            );
        }
        $data = [
            'endpoint' => $tokenEndpoint,
            'code' => $request->input('code'),
            'me' => $request->input('me'),
            'redirect_url' => route('indieauth-callback'),
            'client_id' => route('micropub-client'),
            'state' => $request->input('state'),
        ];
        $token = $this->indieAuthService->getAccessToken($data);

        if (array_key_exists('access_token', $token)) {
            $request->session()->put('me', $token['me']);
            $request->session()->put('token', $token['access_token']);

            return redirect(route('micropub-client'));
        }

        return redirect(route('micropub-client'))->with(
            'error',
            'Unable to get a token from the endpoint'
        );
*/

    /**
     * Log out the user, flush the session data.
     *
     * @return \Illuminate\Routing\RedirectResponse redirect
     */
    public function logout(Request $request)
    {
        $request->session()->flush();

        return redirect(route('micropub-client'));
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
        $auth = $this->indieAuthService->verifyIndieAuthCode($authData);
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

            return response($content)
                    ->header('Content-Type', 'application/x-www-form-urlencoded');
        }
        $content = 'There was an error verifying the authorisation code.';

        return response($content, 400);
    }
}
