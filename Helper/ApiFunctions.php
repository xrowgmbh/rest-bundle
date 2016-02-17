<?php

namespace xrow\restBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setAuthentication(Request $request, $bundle = 'FOS')
    {
        $user = false;
        $session = $request->getSession();
        if ($session->isStarted() === false) {
            $session->start();
        }
        $oauthToken = $this->securityTokenStorage->getToken();
        if ($oauthToken instanceof AnonymousToken || $oauthToken->getUser() === null) {
            try {
                $accesTokenString = $this->getBearerToken($request, $bundle);
            }
            catch (\Exception $e) {
                $exception = $this->errorHandling($e);
                return new JsonResponse(array(
                    'error' => $exception['error'],
                    'error_type' => $exception['type'],
                    'error_description' => $exception['error_description']), $exception['httpCode']);
            }
            if (isset($accesTokenString)) {
                if ($bundle == 'FOS') {
                    $oauthToken = new FOSOAuthToken();
                    $oauthToken->setToken($accesTokenString);
                    try {
                        $oauthToken = $this->container->get('security.authentication.manager')->authenticate($oauthToken);
                    } catch (\Exception $e) {
                        $exception = $this->errorHandling($e);
                        return new JsonResponse(array(
                            'error' => $exception['error'],
                            'error_type' => $exception['type'],
                            'error_description' => $exception['error_description']), $exception['httpCode']);
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
                $userData = array('user' => $this->crmPlugin->getUser($user),
                                  'subscriptions' => $this->crmPlugin->getSubscriptions($user));
                $session->set('CRMUserData', $userData);
                $return = array('session_name' => $session->getName(),
                                'session_id' => $session->getId());
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setCookie(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $sessionName = 'eZSESSID';
        $sessionValue = $request->get('idsv');
        if ($sessionValue !== null) {
            if (isset($_COOKIE[$sessionName])) {
                setcookie($sessionName, null, -1, '/');
                unset($_COOKIE[$sessionName]);
            }
            setcookie($sessionName, $sessionValue, 0, '/', '', 0, 1);
        }
        return new JsonResponse(); 
    }

    /**
     * Get or update user data
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUser(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
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
        $response = new JsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get account data
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
        $response = new JsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get subscriptions
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
        $response = new JsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get subscription
     * 
     * @param Request $request $subscriptionId
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
        $response = new JsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Check password to allow an update of portal_profile_data
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
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
        $response = new JsonResponse('', 204);
        $response->prepare($request);
        return $response;
    }

    /**
     * Get session data
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSession(Request $request, $bundle = 'FOS')
    {
        $user = $this->checkAccessGranted($request, $bundle);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $session = $this->container->get('session');
        if ($session->isStarted() === false) {
            $response = new JsonResponse('', 204);
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteSession(Request $request, $sessionId)
    {
        $sessionName = '';
        $session = $this->container->get('session');
        if ($session->isStarted() !== false && $sessionId != '' && $session->getId() == $sessionId) {
            if (method_exists($this->crmPlugin, 'logout')) {
                $user = null;
                $secureToken = $this->securityTokenStorage->getToken();
                if ($secureToken instanceof TokenInterface)
                    $user = $secureToken->getUser();
                $this->crmPlugin->logout($user);
            }
            $sessionName = $session->getName();
            if (isset($_COOKIE[$sessionName])) {
                setcookie($sessionName, null, -1, '/');
                unset($_COOKIE[$sessionName]);
            }
            $session->invalidate();
        }
        $this->securityTokenStorage->setToken(null);
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
                //throw new AuthenticationException('OAuth2 authentication failed', 0, $e);
                $exception = $this->errorHandling($e);
                return new JsonResponse(array(
                    'error' => $exception['error'],
                    'error_type' => $exception['type'],
                    'error_description' => $exception['error_description']), $exception['httpCode']);
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
     * Gets the right error message and code
     * 
     * @param unknown $e
     * @return array $result
     */
    private function errorHandling($e)
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
        }
        return $result;
    }
}