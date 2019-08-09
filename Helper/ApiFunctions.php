<?php

namespace xrow\restBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken as FOSOAuthToken;
use FOS\OAuthServerBundle\Model\AccessTokenInterface as FOSAccessTokenInterface;
use OAuth2\TokenType\Bearer;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\ServerBundle\Entity\AccessToken as OAuth2AccessToken;
use eZ\Publish\Core\MVC\Symfony\Security\UserWrapped as eZUserWrapped;
use xrow\restBundle\Security\OAuth2Token;
use xrow\restBundle\Exception\OAuth2AuthenticateException;
use xrow\restBundle\Exception\UserException;
use xrow\restBundle\HttpFoundation\xrowJsonResponse as JsonResponse;

class ApiFunctions
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    const HTTP_FOUND = '302 Found';
    const HTTP_BAD_REQUEST = '400 Bad Request';
    const HTTP_UNAUTHORIZED = '401 Unauthorized';
    const HTTP_FORBIDDEN = '403 Forbidden';
    const HTTP_UNAVAILABLE = '503 Service Unavailable';
    const WWW_REALM = 'Service';
    const TOKEN_TYPE_BEARER = 'bearer';

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container){
        $this->container = $container;
        $this->crmPlugin = $this->container->get('xrow_rest.crm.plugin');
        $this->securityTokenStorage = $this->container->get('security.token_storage');
        $this->OAuth2ServerStorage = $this->container->get('xrow.oauth2.server.storage');
    }

    /**
     * For authentication of an user
     * 
     * @param Request $request
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function setAuthentication(Request $request, $bundle = 'FOS')
    {
        $user = false;
        $session = $request->getSession();
        
        $rememberMeValue = $request->get('rememberme');
        if($this->container->hasParameter('expire_time.limit')) {
            $expireLimit = $this->container->getParameter('expire_time.limit');
        } else {
            $expireLimit = 0;
        }
        if($rememberMeValue === 'no') {
            //php7.2 Bug: #75650 Why this warning? "Cannot change session name when session is active"?
            if($session->isStarted()) {
                $session->invalidate();
            }
            session_set_cookie_params($expireLimit);
        }
        if ($session->isStarted() === false) {
            $session->start();
        }
        $oauthToken = $this->securityTokenStorage->getToken();
        if ($oauthToken instanceof AnonymousToken || $oauthToken->getUser() === null) {
            try {
                $accesTokenString = $this->getBearerToken($request, $bundle);
            }
            catch (\Exception $e) {
                return $this->jsonExceptionResponse($e);
            }
            if (isset($accesTokenString)) {
                if ($bundle == 'FOS') {
                    $oauthToken = new FOSOAuthToken();
                    $oauthToken->setToken($accesTokenString);
                    try {
                        $oauthToken = $this->container->get('security.authentication.manager')->authenticate($oauthToken);
                    } catch (\Exception $e) {
                        return $this->jsonExceptionResponse($e);
                    }
                }
                else {
                    $idToken = $this->OAuth2ServerStorage->isAccessTokenOpenID($accesTokenString);
                    if ($idToken && is_array($idToken)) {
                        $this->OAuth2ServerStorage->verifyOpenIDAccessToken($idToken);
                        $userId = $idToken['sub'];
                    }
                    else {
                        $accessToken = $this->verifyAccessToken($accesTokenString, 'OAuth2');
                        if ($accessToken instanceof OAuth2AccessToken)
                            $userId = $accessToken->getUserId();
                    }
                    if (isset($userId)) {
                        $user = $this->container->get('oauth2.user_provider')->loadUserById($userId);
                        if ($user instanceof UserInterface) {
                            $oauthToken = new UsernamePasswordToken($user,
                                                                    $user->getPassword(),
                                                                    'sso',
                                                                    $user->getRoles());
                            $oauthToken->setAttribute('session_state', $accesTokenString);
                            if ($idToken)
                                $oauthToken->setAttribute('id_token', true);
                            $session->set('session_state', $accesTokenString);
                        }
                    }
                }
                if ($oauthToken instanceof TokenInterface) {
                    $this->securityTokenStorage->setToken($oauthToken);
                    if ($bundle != 'FOS') {
                        // Fire the login event
                        // Logging the user in above the way we do it doesn't do this automatically
                        $event = new InteractiveLoginEvent($request, $oauthToken);
                        $this->container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                    }
                }
            }
        }
        $oauthToken = $this->securityTokenStorage->getToken();
        if ($oauthToken instanceof TokenInterface) {
            $user = $oauthToken->getUser();
            if ($user instanceof UserInterface) {
                // With InteractiveLoginEvent we get an eZ User but we would like to handle with our API user
                if ($user instanceof eZUserWrapped) {
                    $user = $user->getWrappedUser();
                }

                // Set subscriptions to session for permissions for legacy login
                try {
                    $userData = array(
                                    'user' => $this->crmPlugin->getUser($user),
                                    'subscriptions' => $this->crmPlugin->getSubscriptions($user)
                                );
                    $session->set('CRMUserData', $userData);
                    $return = array(
                                    'session_name' => $session->getName(),
                                    'session_id' => $session->getId()
                                );
                } catch(UserException $e) {
                    return $this->jsonExceptionResponse($e);
                }
                return new JsonResponse(array(
                    'result' => $return,
                    'type' => 'CONTENT',
                    'message' => 'Authentication successfully'));
            }
        }
        return new JsonResponse(array(
            'error' => 'invalid_grant',
            'error_type' => 'NOUSER',
            'error_description' => 'This user does not have access to this section.'), 403);
    }

    /**
     * Set cookie from API server to my server
     * 
     * @param Request $request
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function setCookie(Request $request, $bundle = 'FOS')
    {
        if($this->container->hasParameter('expire_time.limit')) {
            $expireLimit = time() + $this->container->getParameter('expire_time.limit');
        } else {
            $expireLimit = 0;
        }
        if($this->container->hasParameter('expire_time.nolimit')) {
            $expireNoLimit = time() + $this->container->getParameter('expire_time.nolimit');
        } else {
            $expireNoLimit = 0;
        }
        $expireTime = $expireLimit;
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $newResponse = new JsonResponse();
        $sessionName = 'eZSESSID';
        $sessionValue = $request->get('idsv');
        $rememberMeValue = $request->get('rememberme');
        if($rememberMeValue === "yes") {
            $expireTime = $expireNoLimit;
        }
        if ($sessionValue !== null) {
            if ($request->isSecure()) {
                $cookie = new Cookie($sessionName, $sessionValue, $expireTime, '/', null, 1, 1);
            }
            else {
                $cookie = new Cookie($sessionName, $sessionValue, $expireTime, '/', null, 0, 1);
            }
            $newResponse->headers->setCookie($cookie);
        }
        return $newResponse;
    }

    /**
     * Get or update user data
     * 
     * @param Request $request
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function getUser(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        //$session = $request->getSession();
        //var_dump($session->getId());
        //return 'bla';
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $httpMethod = $request->getMethod();
        if ($httpMethod == 'GET') {
            $CRMUser = $this->crmPlugin->getUser($user);
        }
        elseif ($httpMethod == 'PATCH') {
            $CRMUser = $this->crmPlugin->updateUser($user, $request);
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
        $response = new BaseJsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get account data
     * 
     * @param Request $request
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function getAccount(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $CRMAccount = $this->crmPlugin->getAccount($user);
        if($CRMAccount) {
            return new JsonResponse(array(
                    'result' => $CRMAccount,
                    'type' => 'CONTENT',
                    'message' => 'Account data'));
        }
        $response = new BaseJsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get subscriptions
     * 
     * @param Request $request
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function getSubscriptions(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $CRMUserSubscriptions = $this->crmPlugin->getSubscriptions($user);
        if($CRMUserSubscriptions) {
            $jsonContent = new JsonResponse(array(
                                    'result' => $CRMUserSubscriptions,
                                    'type' => 'CONTENT',
                                    'message' => 'User subscriptions'));
            $jsonContent = $jsonContent->setEncodingOptions(JSON_FORCE_OBJECT);
            return $jsonContent;
        }
        $response = new BaseJsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get subscription
     * 
     * @param Request $request $subscriptionId
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function getSubscription(Request $request, $subscriptionId, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $CRMUserSubscription = $this->crmPlugin->getSubscription($user, $subscriptionId);
        if($CRMUserSubscription) {
            return new JsonResponse(array(
                'result' => $CRMUserSubscription,
                'type' => 'CONTENT',
                'message' => 'User subscription'));
        }
        $response = new BaseJsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Check password to allow an update of portal_profile_data
     * 
     * @param Request $request
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function checkPassword(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $edituser = $request->get('edituser', false);
        if ( ((isset($edituser['username']) && trim($edituser['username']) != '') || (isset($edituser['email']) && trim($edituser['email']) != '')) && (isset($edituser['password']) && trim($edituser['password']) != '') ) {
            $loginData = array('username' => (isset($edituser['username']) && trim($edituser['username']) != '') ? $edituser['username'] : $edituser['email'],
                               'password' => $edituser['password']);
            $checkPassword = $this->crmPlugin->checkPassword($loginData);
            if ($this->crmPlugin->checkPassword($loginData) === true) {
                return new JsonResponse(array(
                                        'result' => true,
                                        'type' => 'CONTENT',
                                        'message' => 'User data'));
            }
        }
        $response = new BaseJsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get session data
     * 
     * @param Request $request
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function getSession(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $session = $request->getSession();
        if ($session->isStarted() === false) {
            $response = new BaseJsonResponse('', 204);
            $response->prepare($request);
            return $response;
        }
        return new JsonResponse(array(
                'result' => array(
                    'session_name' => $session->getName(),
                    'session_id' => $session->getId()),
                'type' => 'CONTENT',
                'message' => 'Session data'));
    }

    /**
     * Logout user
     * 
     * @return \xrow\restBundle\HttpFoundation\xrowJsonResponse
     */
    public function deleteSession(Request $request, $sessionId)
    {
        $sessionName = '';
        $session = $request->getSession();
        $newResponse = new JsonResponse(array(
                                    'result' => null,
                                    'type' => 'LOGOUT',
                                    'message' => 'User is logged out'));
        if ($session->isStarted() !== false && $sessionId != '' && $session->getId() == $sessionId) {
            if (method_exists($this->crmPlugin, 'logout')) {
                $user = null;
                $secureToken = $this->securityTokenStorage->getToken();
                if ($secureToken instanceof TokenInterface) {
                    $user = $secureToken->getUser();
                    if ($user instanceof \eZ\Publish\Core\MVC\Symfony\Security\UserWrapped) {
                        $user = $user->getWrappedUser();
                    }
                }
                $this->crmPlugin->logout($user);
            }
            $sessionName = $session->getName();
            $newResponse->headers->clearCookie($sessionName);
            $session->invalidate();
        }
        $this->securityTokenStorage->setToken(null);
        return $newResponse;
    }

    /**
     * Check the access token
     *
     * @param Request $request
     * @throws AccessDeniedException
     * @return UserInterface, if true | JsonResponse, if false
     */
    public function checkAccessGranted(Request $request, $bundle)
    {
        $user = false;
        $accessTokenString = $this->getBearerToken($request, $bundle);
        if ($accessTokenString !== null) {
            try {
                $idToken = $this->OAuth2ServerStorage->isAccessTokenOpenID($accessTokenString);
                if ($idToken && is_array($idToken)) {
                    if ($this->OAuth2ServerStorage->verifyOpenIDAccessToken($idToken))
                        $user = $this->container->get('oauth2.user_provider')->loadUserById($idToken['sub']);
                }
                else {
                    // throw Exceptions like expired (401) or bad request (400) or forbidden (403)
                    $accessToken = $this->verifyAccessToken($accessTokenString, $bundle);
                    if ($accessToken instanceof FOSAccessTokenInterface){
                        $user = $accessToken->getUser();
                    }
                    else if ($accessToken instanceof OAuth2AccessToken) {
                        $user = $this->container->get('oauth2.user_provider')->loadUserById($accessToken->getUserId());
                    }
                }
            } catch (OAuth2AuthenticateException $e) {
                return $this->jsonExceptionResponse($e);
            }
        }
        elseif ($bundle == 'FOS') {
            $oauthToken = $this->securityTokenStorage->getToken();
            if ($oauthToken instanceof FOSOAuthToken) {
                $user = $oauthToken->getUser();
            }
        }
        if (!$user instanceof UserInterface) {
            return new JsonResponse(array(
                'error' => 'invalid_grant',
                'error_type' => 'NOUSER',
                'error_description' => 'This user does not have access to this section.'), 403);
        }
        return $user;
    }

    /**
     * Gets bearer token out of request
     *
     * @param unknown $request
     * @param string $bundle
     * @return string
     */
    public function getBearerToken(Request $request, $bundle = '')
    {
        $bearerTokenString = null;
        if ($bundle == 'FOS' || $bundle == '') {
            $bearerTokenString = $this->container->get('fos_oauth_server.server')->getBearerToken($request, true);
        }
        if ($bearerTokenString === null) {
            $bearer = new Bearer();
            $oauth2Request = $this->container->get('oauth2.request');
            $oauth2Response = $this->container->get('oauth2.response');
            $bearerTokenString = $bearer->getAccessTokenParameter($oauth2Request, $oauth2Response);
        }
        return $bearerTokenString;
    }

    /**
     * Verifys the state of the token
     * 
     * @param string $tokenParam
     * @throws OAuth2AuthenticateException
     * @return TokenInterface
     */
    public function verifyAccessToken($tokenParam, $bundle = '')
    {
        if ($tokenParam == '') {
            throw new OAuth2AuthenticateException(self::HTTP_BAD_REQUEST, self::TOKEN_TYPE_BEARER, self::WWW_REALM, 'invalid_grant', 'The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.');
        }

        // Get the stored token data (from the implementing subclass)
        if ($bundle == 'FOS' || $bundle == '') {
            $token = $this->container->get('fos_oauth_server.storage')->getAccessToken($tokenParam);
        }
        if (!isset($token)) {
            $token = $this->container->get('doctrine.orm.entity_manager')->getRepository('OAuth2ServerBundle:AccessToken')->find($tokenParam);
        }
        if (!isset($token)) {
            throw new OAuth2AuthenticateException(self::HTTP_UNAUTHORIZED, self::TOKEN_TYPE_BEARER, self::WWW_REALM, 'invalid_grant', 'The access token provided is invalid.');
        }
        // Check token expiration (expires is a mandatory paramter)
        if (method_exists($token, 'hasExpired')) {
            if ($token->hasExpired()) {
                throw new OAuth2AuthenticateException(self::HTTP_UNAUTHORIZED, self::TOKEN_TYPE_BEARER, self::WWW_REALM, 'invalid_grant', 'The access token provided has expired.');
            }
        }
        elseif (method_exists($token, 'getExpires')) {
            if (time() > $token->getExpires()->getTimestamp()) {
                throw new OAuth2AuthenticateException(self::HTTP_UNAUTHORIZED, self::TOKEN_TYPE_BEARER, self::WWW_REALM, 'invalid_grant', 'The access token provided has expired.');
            }
        }
        return $token;
    }

    /**
     * Generate a JSON response containing an error message, error type and code.
     *
     * @param unknown $e
     * @return JsonResponse
     */
    private function jsonExceptionResponse($e)
    {
        $result = array('type' => 'ERROR');
        if ($e instanceof OAuth2AuthenticateException || $e->getPrevious() instanceof OAuth2AuthenticateException) {
            if ($e instanceof OAuth2AuthenticateException)
                $exception = $e;
            else 
                $exception = $e->getPrevious();
            $result['error'] = $exception->getCode();
            $result['error_description'] = $this->container->get('translator')->trans($exception->getDescription());
            $errorCode = $exception->getHttpCode();
            if($errorCode == self::HTTP_BAD_REQUEST)
                $result['httpCode'] = 400;
            elseif($errorCode == self::HTTP_FORBIDDEN)
                $result['httpCode'] = 403;
            elseif($errorCode == self::HTTP_UNAUTHORIZED) {
                $result['httpCode'] = 401;
                if(strpos($exception->getDescription(), 'has expired') !== false)
                    $result['type'] = 'TOKENEXPIREDERROR';
            }
        }
        else {
            $result['error'] = $e->getCode();
            $result['error_description'] = $this->container->get('translator')->trans($e->getMessage());
            $result['httpCode'] = 500;
            $logger = $this->container->get('logger');
            $logger->critical($e->getMessage() . $e->getTraceAsString());
        }
        return new JsonResponse(
            array(
                'error' => $result['error'],
                'error_type' => $result['type'],
                'error_description' => $result['error_description']
            ),
            $result['httpCode']
        );
    }
}