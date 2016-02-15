<?php

namespace xrow\restBundle\Storage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use xrow\restBundle\Exception\OAuth2AuthenticateException;
use OAuth2\Encryption\Jwt as EncryptionUtil;

class OAuth2ServerStorage
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container){
        $this->container = $container;
        $this->OAuth2Server = $this->container->get('oauth2.server');
        $this->OAuth2Request = $this->container->get('oauth2.request');
        $this->OAuth2Response = $this->container->get('oauth2.response');
    }

    /**
     * Creates a request to get the id_token
     *
     * @param JsonResponse $response
     * @return string|\Symfony\Component\HttpFoundation\JsonResponse|unknown
     */
    public function handleAuthorizeRequest(JsonResponse $response)
    {
        if ($user instanceof UserInterface) {
            $oauthToken = new UsernamePasswordToken($user,
                $user->getPassword(),
                'sso',
                $user->getRoles());
            $oauthToken->setAttribute('oiccode', $code);
            $result = $this->get('xrow_rest.api.helper')->setTokenAndUserData($oauthToken, $request, $session, 'OAuth2');
            // Set ops
            $ops = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
            $session->set('ops', $ops);
            setcookie('ops', $ops, 0, '/');
            // Set session_state
            $salt = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
            $session_state = hash('sha256', "{$this->container->getParameter('oauth2.client_id')}{$issuer}{$ops}{$salt}") . '.' . $salt;
            $session->set('session_state', $session_state);
            return $result;
        }
    }

    /**
     * Verifys the OpenID Connect AccessToken
     *
     * @param string $tokenParam
     * @throws OAuth2AuthenticateException
     * @return TokenInterface
     */
    public function verifyOpenIDAccessToken($token)
    {
        $clientId = $this->container->getParameter('oauth2.client_id');
        // Check token expiration (expires is a mandatory paramter)
        if (isset($token['exp'])) {
            if (time() > $token['exp']) {
                throw new OAuth2AuthenticateException(self::HTTP_UNAUTHORIZED, self::TOKEN_TYPE_BEARER, self::WWW_REALM, 'invalid_grant', 'The access token provided has expired.');
            }
        }
        // Check if user is set
        if (!isset($token['sub']) || $token['sub'] <= 0) {
            throw new OAuth2AuthenticateException(self::HTTP_UNAUTHORIZED, self::TOKEN_TYPE_BEARER, self::WWW_REALM, 'invalid_grant', 'The access token provided has no user.');
        }
        return true;
    }

    /**
     * To check and get the OpenID Connect token
     * @param string $accessToken
     * @return array|false
     */
    public function isAccessTokenOpenID($accessToken)
    {
        $clientId = $this->container->getParameter('oauth2.client_id');
        return $this->decodeJwtAccessToken($accessToken, $clientId);
    }

    public function encodeUserId($userId)
    {
        $secret = $this->container->getParameter('secret');
        return base64_encode($secret . $userId);
    }

    public function decodeUserId($decodedString)
    {
        $secret = $this->container->getParameter('secret');
        return preg_replace('/' . $secret . '/', '', base64_decode($decodedString));
    }

    /**
     * To decode the JWT AccessToken
     * 
     * @param unknown $accessToken
     * @param unknown $clientId
     * @return array Jwt token|false
     */
    public function decodeJwtAccessToken($accessToken, $clientId)
    {
        // Get encryption util
        $encryptionUtil = new EncryptionUtil();
        // Get authorization storage
        $public_key = $this->OAuth2Server->getStorage('authorization_code')->getPublicKey($clientId);
        return $encryptionUtil->decode($accessToken, $public_key);
    }
}