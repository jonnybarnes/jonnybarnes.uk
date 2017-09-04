<?php

namespace App\Http\Controllers;

use App\IndieWebUser;
use IndieAuth\Client;
use Illuminate\Http\Request;

class IndieAuthController extends Controller
{
    /**
     * The IndieAuth Client.
     */
    protected $client;

    /**
     * Inject the dependency.
     *
     * @param  \IndieAuth\Client $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
}
