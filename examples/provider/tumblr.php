<?php

/**
 * Example of retrieving an authentication token of the Tumblr service.
 *
 * PHP version 5.4
 *
 * @author     David Desberg <david@daviddesberg.com>
 * @author     Pieter Hordijk <info@pieterhordijk.com>
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\Session;
use OAuth\OAuth1\Service\Tumblr;

/**
 * Bootstrap the example.
 */
require_once __DIR__ . '/bootstrap.php';

// We need to use a persistent storage to save the token, because oauth1 requires the token secret received before'
// the redirect (request token request) in the access token request.
$storage = new Session();

// Setup the credentials for the requests
$credentials = new Credentials(
    $servicesCredentials['tumblr']['key'],
    $servicesCredentials['tumblr']['secret'],
    $currentUri->getAbsoluteUri()
);

// Instantiate the tumblr service using the credentials, http client and storage mechanism for the token
/** @var Tumblr $tumblrService */
$tumblrService = $serviceFactory->createService('tumblr', $credentials, $storage);

if (! empty($_GET['oauth_token'])) {
    $token = $storage->retrieveAccessToken('Tumblr');

    // This was a callback request from tumblr, get the token
    $tumblrService->requestAccessToken(
        $_GET['oauth_token'],
        $_GET['oauth_verifier'],
        $token->getRequestTokenSecret()
    );

    // Send a request now that we have access token
    $result = json_decode($tumblrService->request('user/info'));

    echo 'result: <pre>' . print_r($result, true) . '</pre>';
} elseif (! empty($_GET['go']) && $_GET['go'] === 'go') {
    // extra request needed for oauth1 to request a request token :-)
    $token = $tumblrService->requestRequestToken();

    $url = $tumblrService->getAuthorizationUri(['oauth_token' => $token->getRequestToken()]);
    header('Location: ' . $url);
} else {
    $url = $currentUri->getRelativeUri() . '?go=go';
    echo "<a href='$url'>Login with Tumblr!</a>";
}
