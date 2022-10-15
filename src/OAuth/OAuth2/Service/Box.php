<?php

namespace OAuth\OAuth2\Service;

use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\OAuth2\Token\StdOAuth2Token;

/**
 * Box service.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 *
 * @see https://developers.box.com/oauth/
 */
class Box extends AbstractService
{
    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = [],
        ?UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri, true);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://api.box.com/2.0/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://www.box.com/api/oauth2/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://www.box.com/api/oauth2/token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_BEARER;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || ! is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        $token->setLifeTime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token'], $data['expires_in']);

        $token->setExtraParams($data);

        return $token;
    }
}
