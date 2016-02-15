<?php

namespace xrow\restBundle\Storage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    public function checkUser()
    {
        // Get grantType from request
        $OAuth2UserGrantType = $this->container->get('oauth2.grant_type.user_credentials');
        if (!$grantTypeIdentifier = $this->OAuth2Request->get('grant_type')) {
            return new JsonResponse(array(
                'error' => 'invalid_request',
                'error_type' => 'NOGRANTTYPE',
                'error_description' => 'The grant type was not specified in the request'), 400);
        }
        // Check grantType
        if ($OAuth2UserGrantType->getQuerystringIdentifier() != $grantTypeIdentifier) {
            return new JsonResponse(array(
                'error' => 'unsupported_grant_type',
                'error_type' => 'NOGRANTTYPE',
                'error_description' => sprintf('Grant type "%s" not supported', $grantTypeIdentifier)), 400);
        }
        // Gets the Salesforce user
        if (!$OAuth2UserGrantType->validateRequest($this->OAuth2Request, $this->OAuth2Response)) {
            $error = json_decode($this->OAuth2Response->getContent());
            return new JsonResponse(array(
                'error' => $error->error,
                'error_type' => 'oauth2',
                'error_description' => $error->error_description), $this->OAuth2Response->getStatusCode());
        }
        $requestedScope = $this->OAuth2Request->get('scope');
        $availableScope = $OAuth2UserGrantType->getScope();
        if (strpos($availableScope, ' ') !== false) {
            $availableScopeArray = explode(' ', $availableScope);
            if (in_array($requestedScope, $availableScopeArray))
                $availableScope = $requestedScope;
        }
        if ($requestedScope == '' || $availableScope == '' || ($requestedScope != '' && $availableScope != '' && $requestedScope != $availableScope)) {
            // Optimieren
            return new JsonResponse(array(
                'error' => 'invalid_type',
                'error_type' => 'NOSCOPE',
                'error_description' => 'Scope not found'), 400);
        }
        /*
         * Create now a request to get the id_token
        */
        return $OAuth2UserGrantType->getUserId();
    }

    /**
     * To decode the JWT AccessToken
     * 
     * @param unknown $accessToken
     * @param unknown $clientId
     * @return unknown
     */
    public function decodeJwtAccessToken($accessToken, $clientId)
    {
        // Get encryption util
        $encryptionUtil = new EncryptionUtil();
        // Get authorization storage
        $public_key = $this->OAuth2Server->getStorage('authorization_code')->getPublicKey($clientId);
        $decryptedJwtToken = $encryptionUtil->decode($accessToken, $public_key);
        return $decryptedJwtToken;
    }
}