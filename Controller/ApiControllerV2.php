<?php

namespace xrow\restBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class ApiControllerV2 extends Controller
{
    /**
     * Set an OpenID Connect Token
     * 
     * @Route("/oictoken")
     * @Method({"POST"})
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setOpenIDConnectToken(Request $request)
    {
        $serverStorage = $this->get('xrow.oauth2.server.storage');
        $result = $serverStorage->checkUser();
        if ($result instanceof JsonResponse)
            return $result;
        $userId = $result;
        // Set required POST parameter
        $issuer = $this->getParameter('oauth_baseurl');
        $serverStorage->OAuth2Request->request->set('redirect_uri', $issuer);
        $serverStorage->OAuth2Request->request->set('response_type', 'code');
        $serverStorage->OAuth2Request->request->set('state', md5($issuer));
        // Handle authorize request
        $serverStorage->OAuth2Server->handleAuthorizeRequest($serverStorage->OAuth2Request, $serverStorage->OAuth2Response, true, $userId);
        if ($serverStorage->OAuth2Response->headers->has('Location')) {
            $OAuth2Location = $serverStorage->OAuth2Response->headers->get('Location');
            // Parse the returned URL to get the authorization code
            $parts = parse_url($OAuth2Location);
            parse_str($parts['query'], $query);
            if (isset($query['error'])) {
                return new JsonResponse(array(
                    'error' => 'oauth2',
                    'error_type' => $query['error'],
                    'error_description' => $query['error_description']), $serverStorage->OAuth2Response->getStatusCode());
            }
            $serverStorage->OAuth2Request->request->set('code', $query['code']);
            $clientId = $serverStorage->OAuth2Request->get('client_id');
            // Get authorization grant type
            $grantTypeAuth = $serverStorage->OAuth2Server->getGrantType('authorization_code');
            // Pull the code from storage and verify an "id_token" was added
            $grantTypeAuth->validateRequest($serverStorage->OAuth2Request, $serverStorage->OAuth2Response);
            // Get JwtAccessTokenResponseType to get an jwt sencrypted access token
            $jwtAccessTokenResponseType = $serverStorage->OAuth2Server->getAccessTokenResponseType();
            $accessToken = $grantTypeAuth->createAccessToken($jwtAccessTokenResponseType, $clientId, $userId, 'openid');
            return new JsonResponse($accessToken);
        }
        return new JsonResponse(array(
                    'error' => 'oauth2',
                    'error_type' => 'invalid_request',
                    'error_description' => 'The OAuth2Response does not have a Location header'), 400);
    }

    /**
     * For authentication of an user
     * 
     * @Route("/oicauth")
     * @Method({"POST"})
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setOpenIDConnectAuthenticationAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->setAuthentication($request, 'OAuth2');
    }

    /**
     * Set cookie from API server to my server
     * 
     * @Route("/setcookie")
     * @Method({"GET", "POST"})
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setCookieAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->setCookie($request, 'OAuth2');
    }

    /**
     * Get or update user data
     * 
     * @Route("/user")
     * @Method({"PATCH", "OPTIONS", "GET"})
     * 
     * @param Request $request
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->getUser($request, 'OAuth2');
    }

    /**
     * Get account data
     * 
     * @Route("/account")
     * @Method({"GET"})
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAccountAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->getAccount($request, 'OAuth2');
    }

    /**
     * Get subscriptions
     * 
     * @Route("/subscriptions")
     * @Method({"GET"})
     * 
     * @param Request $request
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSubscriptionsAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->getSubscriptions($request, 'OAuth2');
    }

    /**
     * Get subscription
     *
     * @Route("/subscription/{subscriptionId}")
     * @Method({"GET"})
     * 
     * @param Request $request $subscriptionId
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSubscriptionAction(Request $request, $subscriptionId)
    {
        return $this->get('xrow_rest.api.helper')->getSubscription($request, $subscriptionId, 'OAuth2');
    }

    /**
     * Check password to allow an update of portal_profile_data
     * 
     * @Route("/checkpassword")
     * @Method({"GET"})
     * 
     * @param Request $request
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkPasswordAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->checkPassword($request, 'OAuth2');
    }

    /**
     * Get session data
     *
     * @Route("/session")
     * @Method({"GET", "POST"})
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSessionAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->getSession($request, 'OAuth2');
    }

    /**
     * Logout user
     * 
     * @Route("/sessions/{sessionId}")
     * @Method({"DELETE", "OPTIONS"})
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteSessionAction(Request $request, $sessionId)
    {
        return $this->get('xrow_rest.api.helper')->deleteSession($request, $sessionId);
    }
}