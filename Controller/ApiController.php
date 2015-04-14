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
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use xrow\restBundle\CRM\LoadCRMPlugin;
use xrow\restBundle\Entity\User as APIUser;

class ApiController extends Controller
{
    protected $crmPluginClassObject;

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
     * @param \Symfony\Component\Security\Core\SecurityContextInterface                      $securityContext       The security context.
     * @param \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $authenticationManager The authentication manager.
     * @param \OAuth2\OAuth2                                                                 $serverService
     */
    public function __construct(LoadCRMPlugin $loadCRMPlugin, SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, OAuth2 $serverService)
    {
        $this->crmPluginClassObject = $loadCRMPlugin->crmPluginClass;
        $this->container = $loadCRMPlugin->container;
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->serverService = $serverService;
    }

    /**
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
                        $session->set('athash', $oauthTokenString);
                        $this->securityContext->setToken($returnValue);
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
            $CRMUser = $this->crmPluginClassObject->getUser($user);
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
     * 
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function logoutAction()
    {
        $this->securityContext->setToken(null);
        $this->container->get('session')->invalidate();
        return new JsonResponse(array(
                'result' => null,
                'type' => 'LOGOUT',
                'message' => 'User is logged out'));
    }

    /**
     * 
     * @param Request $request
     * @throws AccessDeniedException
     */
    private function checkAccessGranted(Request $request)
    {
        $user = false;
        $oauthToken = $this->securityContext->getToken();
        $session = $request->getSession();
        $accessTokenString = $session->get('athash');
        try {
            $accessToken = $this->serverService->verifyAccessToken($accessTokenString);
            if ($oauthToken instanceof OAuthToken) {
                $user = $oauthToken->getUser();
            }
            return $user;
        } catch (OAuth2AuthenticateException $e) {
            throw new AuthenticationException('OAuth2 authentication failed', 0, $e);
        }
    }

    /**
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