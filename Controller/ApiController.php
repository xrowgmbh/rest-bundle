<?php

namespace xrow\restBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use FOS\OAuthServerBundle\Model\AccessTokenInterface;

use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;

use xrow\restBundle\Entity\User as APIUser;

class ApiController extends Controller
{
    /**
     * For authentication of an user
     * 
     * @param Request $request
     * @throws AccessDeniedException
     */
    public function setAuthenticationAction(Request $request)
    {
        $user = false;
        try {
            $oauthToken = $this->get('security.context')->getToken();
            $session = $request->getSession();
            if ($session->isStarted() === false) {
                $session->start();
            }
            if ($oauthToken instanceof AnonymousToken) {
                $oauthTokenString = $this->get('fos_oauth_server.server')->getBearerToken($request, true);
                $oauthToken = new OAuthToken();
                $oauthToken->setToken($oauthTokenString);
                if ($oauthToken instanceof OAuthToken) {
                    $tokenString = $oauthToken->getToken();
                    $returnValue = $this->get('security.authentication.manager')->authenticate($oauthToken);
                    if ($returnValue instanceof TokenInterface) {
                        $this->get('security.context')->setToken($returnValue);
                        $session->set('access_token', $oauthTokenString);
                    }
                }
            }
            $oauthToken = $this->get('security.context')->getToken();
            if ($oauthToken instanceof OAuthToken) {
                $user = $oauthToken->getUser();
                if (!$user instanceof APIUser) {
                    return new JsonResponse(array(
                            'error' => 'invalid_grant',
                            'error_type' => 'NOUSER',
                            'error_description' => 'This user does not have access to this section.'), 403);
                }
                // Set subscriptions to session for permissions for legacy login
                $userData = array('user' => $this->get('xrow_rest.crm.plugin')->getUser($user),
                                  'subscriptions' => $this->get('xrow_rest.crm.plugin')->getSubscriptions($user));
                $session->set('CRMUserData', $userData);
                $return = array('session_name' => $session->getName(),
                                'session_id' => $session->getId());
                return new JsonResponse(array(
                        'result' => $return,
                        'type' => 'CONTENT',
                        'message' => 'Authentication successfully'));
            }
        } catch (AuthenticationException $e) {
            $exception = $this->errorHandling($e);
            return new JsonResponse(array(
                    'error' => $exception['error'],
                    'error_type' => $exception['type'],
                    'error_description' => $exception['error_description']), $exception['httpCode']);
        }
    }

    /**
     * Get or update user data
     * 
     * @param Request $request
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserAction(Request $request)
    {
        try {
            $user = $this->checkAccessGranted($request);
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                        'error' => 'invalid_grant',
                        'error_type' => 'NOUSER',
                        'error_description' => 'This user does not have access to this section.'), 403);
            }
            $httpMethod = $request->getMethod();
            if ($httpMethod == 'GET') {
                $CRMUser = $this->get('xrow_rest.crm.plugin')->getUser($user);
            }
            elseif ($httpMethod == 'PATCH') {
                $CRMUser = $this->get('xrow_rest.crm.plugin')->updateUser($user, $request);
            }
            if($CRMUser && !array_key_exists('error', $CRMUser)) {
                $response = new JsonResponse(array(
                                                'result' => $CRMUser,
                                                'type' => 'CONTENT',
                                                'message' => 'User data'));
                return $response;
            }
            if($CRMUser && array_key_exists('error', $CRMUser)) {
                return new JsonResponse(array(
                    'error_description' => $CRMUser['error']), 500);
            }
            return new JsonResponse('', 204);
        } catch (AuthenticationException $e) {
            $exception = $this->errorHandling($e);
            return new JsonResponse(array(
                    'error' => $exception['error'],
                    'error_type' => $exception['type'],
                    'error_description' => $exception['error_description']), $exception['httpCode']);
        }
    }

    /**
     * Get account data
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAccountAction(Request $request)
    {
        try {
            $user = $this->checkAccessGranted($request);
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                        'error' => 'invalid_grant',
                        'error_type' => 'NOUSER',
                        'error_description' => 'This user does not have access to this section.'), 403);
            }
            $CRMAccount = $this->get('xrow_rest.crm.plugin')->getAccount($user);
            if($CRMAccount) {
                return new JsonResponse(array(
                        'result' => $CRMAccount,
                        'type' => 'CONTENT',
                        'message' => 'Account data'));
            }
            return new JsonResponse('', 204);
        } catch (AuthenticationException $e) {
            $exception = $this->errorHandling($e);
            return new JsonResponse(array(
                    'error' => $exception['error'],
                    'error_type' => $exception['type'],
                    'error_description' => $exception['error_description']), $exception['httpCode']);
        }
    }

    /**
     * Get subscriptions
     * 
     * @param Request $request
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSubscriptionsAction(Request $request)
    {
        try {
            $user = $this->checkAccessGranted($request);
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                        'error' => 'invalid_grant',
                        'error_type' => 'NOUSER',
                        'error_description' => 'This user does not have access to this section.'), 403);
            }
            $CRMUserSubscriptions = $this->get('xrow_rest.crm.plugin')->getSubscriptions($user);
            if($CRMUserSubscriptions) {
                $jsonContent = new JsonResponse(array(
                                        'result' => $CRMUserSubscriptions,
                                        'type' => 'CONTENT',
                                        'message' => 'User subscriptions'));
                $jsonContent = $jsonContent->setEncodingOptions(JSON_FORCE_OBJECT);
                return $jsonContent;
            }
            return new JsonResponse('', 204);
        } catch (AuthenticationException $e) {
            $exception = $this->errorHandling($e);
            return new JsonResponse(array(
                    'error' => $exception['error'],
                    'error_type' => $exception['type'],
                    'error_description' => $exception['error_description']), $exception['httpCode']);
        }
    }

    /**
     * Get subscription
     *
     * @param Request $request $subscriptionId
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSubscriptionAction(Request $request, $subscriptionId)
    {
        try {
            $user = $this->checkAccessGranted($request);
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                    'error' => 'invalid_grant',
                    'error_type' => 'NOUSER',
                    'error_description' => 'This user does not have access to this section.'), 403);
            }
            $CRMUserSubscription = $this->get('xrow_rest.crm.plugin')->getSubscription($user, $subscriptionId);
            if($CRMUserSubscription) {
                return new JsonResponse(array(
                    'result' => $CRMUserSubscription,
                    'type' => 'CONTENT',
                    'message' => 'User subscription'));
            }
            return new JsonResponse('', 204);
        } catch (AuthenticationException $e) {
            $exception = $this->errorHandling($e);
            return new JsonResponse(array(
                'error' => $exception['error'],
                'error_type' => $exception['type'],
                'error_description' => $exception['error_description']), $exception['httpCode']);
        }
    }

    /**
     * Check password to allow an update of portal_profile_data
     * 
     * @param Request $request
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkPasswordAction(Request $request)
    {
        try {
            $user = $this->checkAccessGranted($request);
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                    'error' => 'invalid_grant',
                    'error_type' => 'NOUSER',
                    'error_description' => 'This user does not have access to this section.'), 403);
            }
            $edituser = $request->get('edituser', false);
            if ( ((isset($edituser['username']) && trim($edituser['username']) != '') || (isset($edituser['email']) && trim($edituser['email']) != '')) && (isset($edituser['password']) && trim($edituser['password']) != '') ) {
                $loginData = array('username' => (isset($edituser['username']) && trim($edituser['username']) != '') ? $edituser['username'] : $edituser['email'],
                                   'password' => $edituser['password']);
                $checkPassword = $this->get('xrow_rest.crm.plugin')->checkPassword($loginData);
                if ($this->get('xrow_rest.crm.plugin')->checkPassword($loginData) === true) {
                    return new JsonResponse(array(
                                            'result' => true,
                                            'type' => 'CONTENT',
                                            'message' => 'User data'));
                }
            }
            return new JsonResponse('', 204);
        } catch (AuthenticationException $e) {
            $exception = $this->errorHandling($e);
            return new JsonResponse(array(
                'error' => $exception['error'],
                'error_type' => $exception['type'],
                'error_description' => $exception['error_description']), $exception['httpCode']);
        }
    }

    /**
     * Get session data
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSessionAction(Request $request)
    {
        try {
            $user = $this->checkAccessGranted($request);
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                    'error' => 'invalid_grant',
                    'error_type' => 'NOUSER',
                    'error_description' => 'This user does not have access to this section.'), 403);
            }
            $session = $this->container->get('session');
            if ($session->isStarted() === false) {
                return new JsonResponse('', 204);
            }
            return new JsonResponse(array(
                    'result' => array(
                        'session_name' => $session->getName(),
                        'session_id' => $session->getId()),
                    'type' => 'CONTENT',
                    'message' => 'Session data'));

        } catch (AuthenticationException $e) {
            $exception = $this->errorHandling($e);
            return new JsonResponse(array(
                'error' => $exception['error'],
                'error_type' => $exception['type'],
                'error_description' => $exception['error_description']), $exception['httpCode']);
        }
    }

    /**
     * Logout user
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteSessionAction(Request $request, $sessionId)
    {
        $secureContext = $this->get('security.context');
        $sessionName = '';
        $session = $this->container->get('session');
        if ($session->isStarted() !== false && $sessionId != '' && $session->getId() == $sessionId) {
            $crmPlugin = $this->get('xrow_rest.crm.plugin');
            if (method_exists($crmPlugin, 'logout')) {
                $user = null;
                $secureToken = $secureContext->getToken();
                if ($secureToken instanceof TokenInterface)
                    $user = $secureToken->getUser();
                $crmPlugin->logout($user);
            }
            $sessionName = $session->getName();
            if (isset($_COOKIE[$sessionName])) {
                setcookie($sessionName, null, -1, '/');
                unset($_COOKIE[$sessionName]);
            }
            $session->invalidate();
        }
        $secureContext->setToken(null);
        return new JsonResponse(array(
                                    'result' => null,
                                    'type' => 'LOGOUT',
                                    'message' => 'User is logged out'));
    }

    /**
     * Check the access token
     * 
     * @param Request $request
     * @throws AccessDeniedException
     */
    public function checkAccessGranted(Request $request)
    {
        $user = false;
        $oauthTokenString = $this->get('fos_oauth_server.server')->getBearerToken($request, true);
        if ($oauthTokenString !== null) {
            try {
                // throw Exceptions like expired (401) or bad request (400) or forbidden (403)
                $accessToken = $this->get('fos_oauth_server.server')->verifyAccessToken($oauthTokenString);
                if ($accessToken instanceof AccessTokenInterface) {
                    $user = $accessToken->getUser();
                }
            } catch (OAuth2AuthenticateException $e) {
                throw new AuthenticationException('OAuth2 authentication failed', 0, $e);
            }
        }
        else {
            // Check if user is from same domaine
            $oauthToken = $this->get('security.context')->getToken();
            if ($oauthToken instanceof OAuthToken) {
                $user = $oauthToken->getUser();
            }
        }
        return $user;
    }

    /**
     * Get the right error message and code
     * 
     * @param unknown $e
     * @return array $result
     */
    function errorHandling($e)
    {
        $result = array('type' => 'ERROR');
        if ($e instanceof OAuth2AuthenticateException || $e->getPrevious() instanceof OAuth2AuthenticateException) {
            if ($e instanceof OAuth2AuthenticateException)
                $exception = $e;
            else 
                $exception = $e->getPrevious();
            $result['error'] = $exception->getCode();
            $result['error_description'] = $this->get('translator')->trans($exception->getDescription());
            $errorCode = $exception->getHttpCode();
            if($errorCode == OAuth2::HTTP_BAD_REQUEST)
                $result['httpCode'] = 400;
            elseif($errorCode == OAuth2::HTTP_FORBIDDEN)
                $result['httpCode'] = 403;
            elseif($errorCode == OAuth2::HTTP_UNAUTHORIZED)
            {
                $result['httpCode'] = 401;
                if(strpos($exception->getDescription(), 'has expired') !== false)
                    $result['type'] = 'TOKENEXPIREDERROR';
            }
        }
        else {
            $result['error_description'] = $this->get('translator')->trans($e->getMessage());
            $result['httpCode'] = 500;
        }
        return $result;
    }
}