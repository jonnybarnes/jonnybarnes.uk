<?php

declare(strict_types=1);

namespace App\Services;

use IndieAuth\Client;

class IndieAuthService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }
    /**
     * Given a domain, determing the assocaited authorization endpoint,
     * if one exists.
     *
     * @param  string The domain
     * @return string|null
     */
    public function getAuthorizationEndpoint(string $domain): ?string
    {
        $endpoint = $this->client->discoverAuthorizationEndpoint($this->client->normalizeMeURL($domain));
        if ($endpoint === false) {
            return null;
        }

        return $endpoint;
    }

    /**
     * Given an authorization endpoint, build the appropriate authorization URL.
     *
     * @param  string $authEndpoint
     * @param  string $domain
     * @return string
     */
    public function buildAuthorizationURL(string $authEndpoint, string $domain): string
    {
        $state = bin2hex(openssl_random_pseudo_bytes(16));
        session(['state' => $state]);
        $redirectURL = route('indieauth-callback');
        $clientId = route('micropub-client');
        $scope = 'post';
        $authorizationURL = $this->client->buildAuthorizationURL(
            $authEndpoint,
            $this->client->normalizeMeURL($domain),
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
     * @return string|null
     */
    public function getTokenEndpoint(string $domain): ?string
    {
        return $this->client->discoverTokenEndpoint($this->client->normalizeMeURL($domain));
    }

    /**
     * Retrieve a token from the token endpoint.
     *
     * @param  array The relavent data
     * @return array
     */
    public function getAccessToken(array $data): array
    {
        return $this->client->getAccessToken(
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
     * @return array|null
     */
    public function verifyIndieAuthCode(array $data): ?array
    {
        $authEndpoint = $this->client->discoverAuthorizationEndpoint($data['me']);
        if ($authEndpoint) {
            return $this->client->verifyIndieAuthCode(
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
     * @return string|null The endpoint
     */
    public function discoverMicropubEndpoint(string $domain): ?string
    {
        return $this->client->discoverMicropubEndpoint($this->client->normalizeMeURL($domain));
    }
}
