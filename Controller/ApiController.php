<?php

namespace xrow\restBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\DependencyInjection\ContainerInterface;

use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use FOS\OAuthServerBundle\Model\AccessTokenInterface;

use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;

use xrow\restBundle\Entity\User as APIUser;

use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;

class ApiController extends Controller
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;
    
    /**
     * @var \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface
     */
    protected $authenticationManager;
    
    /**
     * @var \OAuth2\OAuth2
     */
    protected $serverService;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface                      $container
     * @param \Symfony\Component\Security\Core\SecurityContextInterface                      $securityContext       The security context.
     * @param \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $authenticationManager The authentication manager.
     * @param \OAuth2\OAuth2                                                                 $serverService
     */
    public function __construct(ContainerInterface $container, SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, OAuth2 $serverService)
    {
        $this->container = $container;
        $crmClassName = $this->container->getParameter('xrow_rest.plugins.crmclass');
        $this->crmPluginClassObject = new $crmClassName();
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->serverService = $serverService;
    }

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
            $oauthToken = $this->securityContext->getToken();
            if ($oauthToken instanceof AnonymousToken) {
                $oauthTokenString = $this->serverService->getBearerToken($request, true);
                $oauthToken = new OAuthToken();
                $oauthToken->setToken($oauthTokenString);
                if ($oauthToken instanceof OAuthToken) {
                    $tokenString = $oauthToken->getToken();
                    $returnValue = $this->authenticationManager->authenticate($oauthToken);
                    if ($returnValue instanceof TokenInterface) {
                        $session = $request->getSession();
                        if ($session->isStarted() === false) {
                            $session->start();
                        }
                        $this->securityContext->setToken($returnValue);
                        // login new eZ User: siehe Bemerkung in der Funktion loginAPIUser
                        #$this->loginAPIUser($request, $returnValue);
                    }
                }
            }
            $oauthToken = $this->securityContext->getToken();
            if ($oauthToken instanceof OAuthToken) {
                $user = $oauthToken->getUser();
                if (!$user instanceof APIUser) {
                    return new JsonResponse(array(
                            'error' => 'invalid_grant',
                            'error_type' => 'NOUSER',
                            'error_description' => 'This user does not have access to this section.'), 403);
                }
                return new JsonResponse(array(
                        'result' => $user->getId(),
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
     * Problem: der eZ User hat nicht die Daten des SF-Users. 
     * Das muss noch mal etwas überdacht werden und dann erst darf der 
     * neue "zusammengelegte" User mit $this->securityContext->setUser($user) auch gesetzt werden
     * 
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param Symfony\Component\Security\Core\Authentication\Token\TokenInterface $returnValue
     */
    function loginAPIUser($request, $returnValue)
    {
        // Get eZUserID from user with read-rights
        $eZUserID = $this->container->getParameter('eZUserWithAbo');
        // Get some eZ services
        $repository = $this->container->get('ezpublish.api.repository');
        $legacyKernel = $this->container->get('ezpublish_legacy.kernel');
        // Load the eZ User
        $currentEzUser = $repository->getUserService()->loadUser($eZUserID);
        // Set user in repository
        $repository->setCurrentUser($currentEzUser);
        // Login user for eZ new stack
        $event = new InteractiveLoginEvent($request, $returnValue);
        $event->setApiUser($currentEzUser);
        // Login user for eZ legacy stack
        $result = $legacyKernel()->runCallback(
                function () use ( $currentEzUser )
                {
                    $legacyUser = \eZUser::fetch( $currentEzUser->id );
                    \eZUser::setCurrentlyLoggedInUser( $legacyUser, $legacyUser->attribute( 'contentobject_id' ), \eZUser::NO_SESSION_REGENERATE );
                },
                false,
                false
        );
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
                $CRMUser = $this->crmPluginClassObject->getUser($user);
            }
            elseif ($httpMethod == 'PATCH') {
                $CRMUser = $this->crmPluginClassObject->updateUser($user, $request);
            }
            if($CRMUser) {
                return new JsonResponse(array(
                            'result' => $CRMUser,
                            'type' => 'CONTENT',
                            'message' => 'User data'));
            }
            return new JsonResponse(array(
                            'result' => null,
                            'type' => 'NOCONTENT',
                            'message' => 'User not found'), 204);
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
            $CRMAccount = $this->crmPluginClassObject->getAccount($user);
            if($CRMAccount) {
                return new JsonResponse(array(
                        'result' => $CRMAccount,
                        'type' => 'CONTENT',
                        'message' => 'Account data'));
            }
            return new JsonResponse(array(
                    'result' => null,
                    'type' => 'NOCONTENT',
                    'message' => 'User not found'), 204);
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
            $CRMUserSubscriptions = $this->crmPluginClassObject->getSubscriptions($user);
            if($CRMUserSubscriptions) {
                return new JsonResponse(array(
                            'result' => $CRMUserSubscriptions,
                            'type' => 'CONTENT',
                            'message' => 'User subscriptions'));
            }
            return new JsonResponse(array(
                            'result' => null,
                            'type' => 'NOCONTENT',
                            'message' => 'User does not have subscriptions'), 204);
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
            $username = $request->get('username', null);
            $password = $request->get('password', null);
            if ($username !== null && $password !== null) {
                $loginData = array('username' => $username, 
                                   'password' => $password);
                $return = $this->crmPluginClassObject->checkPassword($loginData);
                if($this->crmPluginClassObject->checkPassword($loginData) === true) {
                    return new JsonResponse(array(
                        'result' => true,
                        'type' => 'CONTENT',
                        'message' => 'User data'));
                }
            }
            return new JsonResponse(array(
                'result' => null,
                'type' => 'NOCONTENT',
                'message' => 'User not found'), 204);
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
    public function logoutAction(Request $request)
    {
        $oauthTokenString = $this->serverService->getBearerToken($request, true);
        if ($oauthTokenString !== null) {
            try {
                $accessToken = $this->serverService->verifyAccessToken($oauthTokenString);
                if ($accessToken instanceof AccessTokenInterface) {
                    $user = $accessToken->setExpiresAt(time());
                }
            } catch (OAuth2AuthenticateException $e) {
                $walk = true;
            }
        }
        $this->securityContext->setToken(null);
        $this->container->get('session')->invalidate();
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
    private function checkAccessGranted(Request $request)
    {
        $user = false;
        $oauthTokenString = $this->serverService->getBearerToken($request, true);
        if ($oauthTokenString !== null) {
            try {
                // throw Exceptions like expired (401) or bad request (400) or forbidden (403)
                $accessToken = $this->serverService->verifyAccessToken($oauthTokenString);
                if ($accessToken instanceof AccessTokenInterface) {
                    $user = $accessToken->getUser();
                }
            } catch (OAuth2AuthenticateException $e) {
                throw new AuthenticationException('OAuth2 authentication failed', 0, $e);
            }
        }
        else {
            // Check if user is from same domaine
            $oauthToken = $this->securityContext->getToken();
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
            $result['error_description'] = $exception->getDescription();
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
            $result['error_description'] = $e->getMessage();
            $result['httpCode'] = $e->getCode();
        }
        return $result;
    }
}