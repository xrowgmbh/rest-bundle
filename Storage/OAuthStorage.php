<?php

namespace xrow\restBundle\Storage;

use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface;
use FOS\OAuthServerBundle\Model\AuthCodeManagerInterface;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use FOS\OAuthServerBundle\Model\ClientInterface;
use FOS\OAuthServerBundle\Storage\GrantExtensionDispatcherInterface;
use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\OAuth2;
use OAuth2\OAuth2ServerException;
use OAuth2\IOAuth2RefreshTokens;
use OAuth2\IOAuth2GrantUser;
use OAuth2\IOAuth2GrantCode;
use OAuth2\IOAuth2GrantImplicit;
use OAuth2\IOAuth2GrantClient;
use OAuth2\IOAuth2GrantExtension;
use OAuth2\Model\IOAuth2Client;

class OAuthStorage implements IOAuth2RefreshTokens, IOAuth2GrantUser, IOAuth2GrantCode, IOAuth2GrantImplicit,
    IOAuth2GrantClient, IOAuth2GrantExtension, GrantExtensionDispatcherInterface
{
    /**
     * @var \FOS\OAuthServerBundle\Model\ClientManagerInterface
     */
    protected $clientManager;

    /**
     * @var \FOS\OAuthServerBundle\Model\AccessTokenManagerInterface
     */
    protected $accessTokenManager;

    /**
     * @var \FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface
     */
    protected $refreshTokenManager;

    /**
     * @var \FOS\OAuthServerBundle\Model\AuthCodeManagerInterface;
     */
    protected $authCodeManager;

    /**
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var array [uri] => GrantExtensionInterface
     */
    protected $grantExtensions;
    
    protected $container;

    /**
     * @param \FOS\OAuthServerBundle\Model\ClientManagerInterface                   $clientManager
     * @param \FOS\OAuthServerBundle\Model\AccessTokenManagerInterface              $accessTokenManager
     * @param \FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface             $refreshTokenManager
     * @param \FOS\OAuthServerBundle\Model\AuthCodeManagerInterface                 $authCodeManager
     * @param null|\Symfony\Component\Security\Core\User\UserProviderInterface      $userProvider
     * @param null|\Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     */
    public function __construct(ClientManagerInterface $clientManager, AccessTokenManagerInterface $accessTokenManager,
        RefreshTokenManagerInterface $refreshTokenManager, AuthCodeManagerInterface $authCodeManager,
        UserProviderInterface $userProvider = null, EncoderFactoryInterface $encoderFactory = null, ContainerInterface $container)
    {
        $this->clientManager = $clientManager;
        $this->accessTokenManager = $accessTokenManager;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->authCodeManager = $authCodeManager;
        $this->userProvider = $userProvider;
        $this->encoderFactory = $encoderFactory;

        $this->container = $container;

        $this->grantExtensions = array();
    }

    /**
     * {@inheritdoc}
     */
    public function setGrantExtension($uri, GrantExtensionInterface $grantExtension)
    {
        $this->grantExtensions[$uri] = $grantExtension;
    }

    public function getClient($clientId)
    {
        return $this->clientManager->findClientByPublicId($clientId);
    }

    public function checkClientCredentials(IOAuth2Client $client, $client_secret = null)
    {
        if (!$client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client has to implement the ClientInterface');
        }

        return $client->checkSecret($client_secret);
    }

    public function checkClientCredentialsGrant(IOAuth2Client $client, $client_secret)
    {
        return $this->checkClientCredentials($client, $client_secret);
    }

    public function getAccessToken($token)
    {
        return $this->accessTokenManager->findTokenByToken($token);
    }

    public function createAccessToken($tokenString, IOAuth2Client $client, $data, $expires, $scope = null)
    {
        if (!$client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client has to implement the ClientInterface');
        }

        $token = $this->accessTokenManager->createToken();
        $token->setToken($tokenString);
        $token->setClient($client);
        $token->setExpiresAt($expires);
        $token->setScope($scope);

        if (null !== $data) {
            $token->setUser($data);
        }

        $this->accessTokenManager->updateToken($token);
        return $token;
    }

    public function checkRestrictedGrantType(IOAuth2Client $client, $grant_type)
    {
        if (!$client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client has to implement the ClientInterface');
        }

        return in_array($grant_type, $client->getAllowedGrantTypes(), true);
    }

    public function checkUserCredentials(IOAuth2Client $client, $username, $password)
    {
        if (!$client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client has to implement the ClientInterface');
        }

        try {
            $user = $this->userProvider->loadUserFromCRM($username, $password);
        } catch (AuthenticationException $e) {
            return false;
        }

        if (null !== $user) {
            if (is_string($user) && $user == 'NOTACTIVE')
                throw new \OAuth2\OAuth2ServerException(\OAuth2\OAuth2::HTTP_BAD_REQUEST, \OAuth2\OAuth2::ERROR_INVALID_GRANT, $this->container->get('translator')->trans("Your account has to be activated"));
            if (is_string($user) && strpos($user, 'LOCKOUT') !== false) {
                $errorText = explode("=>", $user);
                throw new \OAuth2\OAuth2ServerException(\OAuth2\OAuth2::HTTP_BAD_REQUEST, \OAuth2\OAuth2::ERROR_INVALID_GRANT, $errorText[1]);
            }
            return array(
                'data' => $user,
            );
        }
        else {
            throw new \OAuth2\OAuth2ServerException(\OAuth2\OAuth2::HTTP_BAD_REQUEST, \OAuth2\OAuth2::ERROR_INVALID_GRANT, $this->container->get('translator')->trans("Invalid username and password combination"));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthCode($code)
    {
        return $this->authCodeManager->findAuthCodeByToken($code);
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthCode($code, IOAuth2Client $client, $data, $redirect_uri, $expires, $scope = NULL)
    {
        if (!$client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client has to implement the ClientInterface');
        }

        $authCode = $this->authCodeManager->createAuthCode();
        $authCode->setToken($code);
        $authCode->setClient($client);
        $authCode->setUser($data);
        $authCode->setRedirectUri($redirect_uri);
        $authCode->setExpiresAt($expires);
        $authCode->setScope($scope);
        $this->authCodeManager->updateAuthCode($authCode);

        return $authCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getRefreshToken($tokenString)
    {
        return $this->refreshTokenManager->findTokenByToken($tokenString);
    }

    /**
     * {@inheritdoc}
     */
    public function createRefreshToken($tokenString, IOAuth2Client $client, $data, $expires, $scope = NULL)
    {
        if (!$client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client has to implement the ClientInterface');
        }

        $token = $this->refreshTokenManager->createToken();
        $token->setToken($tokenString);
        $token->setClient($client);
        $token->setExpiresAt($expires);
        $token->setScope($scope);

        if (null !== $data) {
            $token->setUser($data);
        }

        $this->refreshTokenManager->updateToken($token);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetRefreshToken($tokenString)
    {
        $token = $this->refreshTokenManager->findTokenByToken($tokenString);

        if (null !== $token) {
            $this->refreshTokenManager->deleteToken($token);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkGrantExtension(IOAuth2Client $client, $uri, array $inputData, array $authHeaders)
    {
        if (!isset($this->grantExtensions[$uri])) {
            throw new OAuth2ServerException(OAuth2::HTTP_BAD_REQUEST, OAuth2::ERROR_UNSUPPORTED_GRANT_TYPE);
        }

        $grantExtension = $this->grantExtensions[$uri];

        return $grantExtension->checkGrantExtension($client, $inputData, $authHeaders);
    }

    /**
     * {@inheritdoc}
     */
    public function markAuthCodeAsUsed($code)
    {
        $authCode = $this->authCodeManager->findAuthCodeByToken($code);
        if (null !== $authCode) {
            $this->authCodeManager->deleteAuthCode($authCode);
        }
    }
}