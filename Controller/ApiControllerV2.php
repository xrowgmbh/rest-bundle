<?php

namespace xrow\restBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class ApiControllerV2 extends Controller
{
    /**
     * For authentication of an user
     * 
     * @Route("/auth")
     * @Method({"GET", "POST"})
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setAuthenticationAction(Request $request)
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
        return $this->get('xrow_rest.api.helper')->deleteSession($request);
    }
}