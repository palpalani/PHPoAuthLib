<?php

namespace OAuth\Common\Storage;

use OAuth\Common\Storage\Exception\AuthorizationStateNotFoundException;
use OAuth\Common\Storage\Exception\TokenNotFoundException;
use OAuth\Common\Token\TokenInterface;

/**
 * Stores a token in a PHP session.
 */
class Session implements TokenStorageInterface
{
    /**
     * @var bool
     */
    protected $startSession;

    /**
     * @var string
     */
    protected $sessionVariableName;

    /**
     * @var string
     */
    protected $stateVariableName;

    /**
     * @param  bool  $startSession whether or not to start the session upon construction
     * @param  string  $sessionVariableName the variable name to use within the _SESSION superglobal
     * @param  string  $stateVariableName
     */
    public function __construct(
        $startSession = true,
        $sessionVariableName = 'lusitanian-oauth-token',
        $stateVariableName = 'lusitanian-oauth-state'
    ) {
        if ($startSession && ! $this->sessionHasStarted()) {
            session_start();
        }

        $this->startSession = $startSession;
        $this->sessionVariableName = $sessionVariableName;
        $this->stateVariableName = $stateVariableName;
        if (! isset($_SESSION[$sessionVariableName])) {
            $_SESSION[$sessionVariableName] = [];
        }
        if (! isset($_SESSION[$stateVariableName])) {
            $_SESSION[$stateVariableName] = [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveAccessToken($service)
    {
        if ($this->hasAccessToken($service)) {
            return unserialize($_SESSION[$this->sessionVariableName][$service]);
        }

        throw new TokenNotFoundException('Token not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritdoc}
     */
    public function storeAccessToken($service, TokenInterface $token)
    {
        $serializedToken = serialize($token);

        if (isset($_SESSION[$this->sessionVariableName])
            && is_array($_SESSION[$this->sessionVariableName])
        ) {
            $_SESSION[$this->sessionVariableName][$service] = $serializedToken;
        } else {
            $_SESSION[$this->sessionVariableName] = [
                $service => $serializedToken,
            ];
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAccessToken($service)
    {
        return isset($_SESSION[$this->sessionVariableName], $_SESSION[$this->sessionVariableName][$service]);
    }

    /**
     * {@inheritdoc}
     */
    public function clearToken($service)
    {
        if (array_key_exists($service, $_SESSION[$this->sessionVariableName])) {
            unset($_SESSION[$this->sessionVariableName][$service]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllTokens()
    {
        unset($_SESSION[$this->sessionVariableName]);

        // allow chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function storeAuthorizationState($service, $state)
    {
        if (isset($_SESSION[$this->stateVariableName])
            && is_array($_SESSION[$this->stateVariableName])
        ) {
            $_SESSION[$this->stateVariableName][$service] = $state;
        } else {
            $_SESSION[$this->stateVariableName] = [
                $service => $state,
            ];
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAuthorizationState($service)
    {
        return isset($_SESSION[$this->stateVariableName], $_SESSION[$this->stateVariableName][$service]);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveAuthorizationState($service)
    {
        if ($this->hasAuthorizationState($service)) {
            return $_SESSION[$this->stateVariableName][$service];
        }

        throw new AuthorizationStateNotFoundException('State not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritdoc}
     */
    public function clearAuthorizationState($service)
    {
        if (array_key_exists($service, $_SESSION[$this->stateVariableName])) {
            unset($_SESSION[$this->stateVariableName][$service]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllAuthorizationStates()
    {
        unset($_SESSION[$this->stateVariableName]);

        // allow chaining
        return $this;
    }

    public function __destruct()
    {
        if ($this->startSession) {
            session_write_close();
        }
    }

    /**
     * Determine if the session has started.
     *
     * @url http://stackoverflow.com/a/18542272/1470961
     *
     * @return bool
     */
    protected function sessionHasStarted()
    {
        // For more modern PHP versions we use a more reliable method.
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            return session_status() != PHP_SESSION_NONE;
        }

        // Below PHP 5.4 we should test for the current session ID.
        return session_id() !== '';
    }
}
