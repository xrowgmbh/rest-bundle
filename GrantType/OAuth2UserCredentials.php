<?php

namespace xrow\restBundle\GrantType;

use OAuth2\GrantType\GrantTypeInterface;
use OAuth2\Storage\UserCredentialsInterface;
use OAuth2\ResponseType\AccessTokenInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class OAuth2UserCredentials implements GrantTypeInterface
{
    protected $storage;
    public $container;

    public $userInfo;

    /**
     * @param OAuth2\Storage\UserCredentialsInterface $storage REQUIRED Storage class for retrieving user credentials information
     * @param Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(UserCredentialsInterface $storage, ContainerInterface $container)
    {
        $this->storage = $storage;
        $this->container = $container;
    }

    public function getQuerystringIdentifier()
    {
        return 'password';
    }

    public function validateRequest(RequestInterface $request, ResponseInterface $response)
    {

        if (!$request->get("password") || !$request->get("username")) {
            $response->setError(400, 'invalid_request', $this->container->get('translator')->trans("Invalid username and password combination"));

            return null;
        }
        $user = $this->storage->checkUserCredentials($request->get("username"), $request->get("password"));
        if (is_array($user) && isset($user['error'])) {
            $response->setError(400, 'invalid_grant', $user['error'][2]);

            return null;
        }

        $userInfo = $this->storage->getUserDetails('');
        if (empty($userInfo)) {
            $response->setError(400, 'invalid_grant', 'Unable to retrieve user information');

            return null;
        }

        if (!isset($userInfo['user_id'])) {
            throw new \LogicException("you must set the user_id on the array returned by getUserDetails");
        }

        $this->userInfo = $userInfo;

        return true;
    }

    public function getClientId()
    {
        return null;
    }

    public function getUserId()
    {
        return isset($this->userInfo['user_id']) ? $this->userInfo['user_id'] : null;
    }

    public function getScope()
    {
        return isset($this->userInfo['scope']) ? $this->userInfo['scope'] : null;
    }

    public function createAccessToken(AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        return $accessToken->createAccessToken($client_id, $user_id, $scope);
    }
}
