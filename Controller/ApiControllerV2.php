<?php

namespace xrow\restBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use xrow\restBundle\HttpFoundation\xrowJsonResponse;

class ApiControllerV2 extends Controller
{
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
        $subscriptionsObject = $this->get('xrow_rest.api.helper')->getSubscriptions($request, 'OAuth2');
        $subscriptionsList = json_decode($subscriptionsObject->getContent());
        foreach ($subscriptionsList->result as $key => $subscriptionsItem)
        {
            if ($subscriptionsItem->digital === '0') {
                unset($subscriptionsList->result->$key);
            }
        }
        $jsonContent = new xrowJsonResponse(array(
            'result' => (array)$subscriptionsList->result,
            'type' => 'CONTENT',
            'message' => 'User subscriptions'));
        $jsonContent = $jsonContent->setEncodingOptions(JSON_FORCE_OBJECT);
        
        return $jsonContent;
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


    /**
     * To check session via iframe for OpenID Connect
     * We need here only the access_token and client id, 
     * if both are empty the session will be invalid and we should logout the user
     *
     * @Route("/check_session_iframe")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     */
    public function checkSessionIframeAction(Request $request)
    {
        $parameters = array();
        $session = $request->getSession();
        $session_state = '';
        $clientId = '';
        if ($session->isStarted() !== false) {
            $oauthToken = $this->get('security.token_storage')->getToken();
            if ($oauthToken instanceof TokenInterface) {
                $user = $oauthToken->getUser();

                if ($user instanceof \eZ\Publish\Core\MVC\Symfony\Security\UserWrapped) {
                    $user = $user->getWrappedUser();
                }

                if ($user instanceof UserInterface) {
                    $parameters['user_id'] = $user->getId();
                    if ($session->has('session_state')) {
                        $session_state = $session->get('session_state');
                        $OAuth2ServerStorage = $this->get('xrow.oauth2.server.storage');
                        $clientId = $this->getParameter('oauth2.client_id');
                        $token = $OAuth2ServerStorage->decodeJwtAccessToken($session_state, $clientId);
                        try {
                            $OAuth2ServerStorage->verifyOpenIDAccessToken($token);
                        }
                        catch(\Exception $e) {
                            // do nothing
                        }
                    }
                }
            }
        }
        return $this->render('xrowRestBundle::open_id_connect_iframe_op.html.twig',
            array('session_state' => $session_state, 'clientId' => $clientId),
            $this->get('wuv.helper.functions')->getNoCacheResponse());
    }

    /**
     * Get session data to all selected domains
     *
     * @Route("/oicsession")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setOpenIDConnectSessionToAllowedDomainsAction(Request $request)
    {
        $sessionName = 'eZSESSID';
        $sessionValue = $request->get('idsv');
        $newResponse = new Response();
        if ($sessionValue !== null) {
            if ($request->isSecure()) {
                $cookie = new Cookie($sessionName, $sessionValue, 0, '/', null, 1, 1);
            }
            else {
                $cookie = new Cookie($sessionName, $sessionValue, 0, '/', null, 0, 1);
            }
            $newResponse->headers->setCookie($cookie);
        }
        return $newResponse;
    }

    /**
     * Check session for set localStorage token
     *
     * @Route("/storage")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setLocalstorageTokenAction(Request $request)
    {
        $parameters = array();
        $session = $request->getSession();
        if ($session->isStarted() !== false) {
            $oauthToken = $this->get('security.token_storage')->getToken();
            if ($oauthToken instanceof TokenInterface) {
                $user = $oauthToken->getUser();

                if ($user instanceof \eZ\Publish\Core\MVC\Symfony\Security\UserWrapped) {
                    $user = $user->getWrappedUser();
                }

                if ($user instanceof UserInterface) {
                    $parameters['user_id'] = $user->getId();
                    if ($session->has('session_state')) {
                        $session_state = $session->get('session_state');
                        $OAuth2ServerStorage = $this->get('xrow.oauth2.server.storage');
                        $clientId = $this->getParameter('oauth2.client_id');
                        $token = $OAuth2ServerStorage->decodeJwtAccessToken($session_state, $clientId);
                        try {
                            $OAuth2ServerStorage->verifyOpenIDAccessToken($token);
                            $result = array('session_state' => $session_state);
                            $qb = $this->get('doctrine.orm.entity_manager')->getRepository('OAuth2ServerBundle:RefreshToken')->createQueryBuilder('token');
                            $refreshTokenResult = $qb->select('token')
                                ->where('token.user_id = ?1')
                                ->setParameter(1, $parameters['user_id'])
                                ->orderBy('token.expires', 'DESC')
                                ->setFirstResult(0)
                                ->setMaxResults(1)
                                ->getQuery()
                                ->getResult();
                            if (isset($refreshTokenResult[0]))
                                $result['refresh_token'] = $refreshTokenResult[0]->getToken();
                            return new JsonResponse(array(
                                'result' => $result,
                                'type' => 'CONTENT',
                                'message' => 'AccessToken is valid'));
                        }
                        catch(\Exception $e) {
                            // Do nothing
                        }
                    }
                }
            }
        }
        return new JsonResponse();
    }
}