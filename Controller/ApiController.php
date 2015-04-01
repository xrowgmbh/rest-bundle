<?php

namespace xrow\restBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use xrow\restBundle\CRM\LoadCRMPlugin;
use xrow\restBundle\Entity\User as APIUser;

class ApiController extends Controller
{
    protected $crmPluginClassObject;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Controller\Controller
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
     * @var \AuthToken
     */
    protected $bearerToken;

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserAction(Request $request)
    {
        try {
            $oauthToken = $this->checkAccessGranted($request);
            $user = $oauthToken->getUser();
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                        'error' => 'invalid_grant',
                        'error_type' => 'NOUSER',
                        'error_description' => 'This user does not have access to this section.'), 403);
            }
            $CRMUser = $this->crmPluginClassObject->getUserData($user);
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
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getSubscriptionsAction(Request $request)
    {
        try {
            $oauthToken = $this->checkAccessGranted($request);
            $user = $oauthToken->getUser();
            if (!$user instanceof APIUser) {
                return new JsonResponse(array(
                        'error' => 'invalid_grant',
                        'error_type' => 'NOUSER',
                        'error_description' => 'This user does not have access to this section.'), 403);
            }
            $CRMUserSubscriptions = $this->crmPluginClassObject->getUserSubscriptions($user);
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
        if(null === $this->bearerToken){
            $oauthToken = $this->securityContext->getToken();
            $oauthTokenString = $this->serverService->getBearerToken($request, true);
            $oauthToken = new OAuthToken();
            $oauthToken->setToken($oauthTokenString);
            if($oauthToken instanceof OAuthToken) {
                $tokenString = $oauthToken->getToken();
                $returnValue = $this->authenticationManager->authenticate($oauthToken);
                if ($returnValue instanceof TokenInterface) {
                    $this->securityContext->setToken($returnValue);
                    $this->bearerToken = $this->securityContext->getToken();
                    return $this->bearerToken;
                }
            }
        }
        else {
            return $this->bearerToken;
        }
    }

    function errorHandling($e)
    {
        $result = array('type' => 'ERROR');
        if($e->getPrevious() instanceof OAuth2AuthenticateException) {
            $previousException = $e->getPrevious();
            $result['error'] = $e->getCode();
            $result['error_description'] = $previousException->getDescription();
            $errorCode = $previousException->getHttpCode();
            if($errorCode == OAuth2::HTTP_BAD_REQUEST)
                $result['httpCode'] = 400;
            elseif($errorCode == OAuth2::HTTP_FORBIDDEN)
                $result['httpCode'] = 403;
            elseif($errorCode == OAuth2::HTTP_UNAUTHORIZED)
            {
                $result['httpCode'] = 401;
                if(strpos($previousException->getDescription(), 'has expired') !== false)
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