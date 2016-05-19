<?php

namespace App\Services;

class IndieAuthService
{
    /**
     * Given a domain, determing the assocaited authorization endpoint,
     * if one exists.
     *
     * @param  string The domain
     * @param  \IndieAuth\Client $client
     * @return string|null
     */
    public function getAuthorizationEndpoint($domain, $client)
    {
        return $client->discoverAuthorizationEndpoint($client->normalizeMeURL($domain));
    }

    /**
     * Given an authorization endpoint, build the appropriate authorization URL.
     *
     * @param  string $authEndpoint
     * @param  string $domain
     * @param  \IndieAuth\Client $client
     * @return string
     */
    public function buildAuthorizationURL($authEndpoint, $domain, $client)
    {
        $domain = $client->normalizeMeURL($domain);
        $state = bin2hex(openssl_random_pseudo_bytes(16));
        session(['state' => $state]);
        $redirectURL = config('app.url') . '/indieauth';
        $clientId = config('app.url') . '/notes/new';
        $scope = 'post';
        $authorizationURL = $client->buildAuthorizationURL(
            $authEndpoint,
            $domain,
            $redirectURL,
            $clientId,
            $state,
            $scope
        );

        return $authorizationURL;
    }

    /**
     * Discover the token endpoint for a given domain.
     *
     * @param  string The domain
     * @param  \IndieAuth\Client $client
     * @return string|null
     */
    public function getTokenEndpoint($domain, $client)
    {
        return $client->discoverTokenEndpoint($domain);
    }

    /**
     * Retrieve a token from the token endpoint.
     *
     * @param  array The relavent data
     * @param  \IndieAuth\Client $client
     * @return array
     */
    public function getAccessToken(array $data, $client)
    {
        return $client->getAccessToken(
            $data['endpoint'],
            $data['code'],
            $data['me'],
            $data['redirect_url'],
            $data['client_id'],
            $data['state']
        );
    }

    /**
     * Determine the Authorization endpoint, then verify the suplied code is
     * valid.
     *
     * @param  array The data.
     * @param  \IndieAuth\Client $client
     * @return array|null
     */
    public function verifyIndieAuthCode(array $data, $client)
    {
        $authEndpoint = $client->discoverAuthorizationEndpoint($data['me']);
        if ($authEndpoint) {
            return $client->verifyIndieAuthCode(
                $authEndpoint,
                $data['code'],
                $data['me'],
                $data['redirect_url'],
                $data['client_id'],
                $data['state']
            );
        }
    }

    /**
     * Determine the micropub endpoint.
     *
     * @param  string $domain
     * @param  \IndieAuth\Client $client
     * @return string The endpoint
     */
    public function discoverMicropubEndpoint($domain, $client)
    {
        return $client->discoverMicropubEndpoint($client->normalizeMeURL($domain));
    }
}
