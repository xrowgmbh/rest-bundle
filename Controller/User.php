<?php

namespace xrow\restBundle\Controller;

use eZ\Publish\Core\REST\Common\Message;
use eZ\Publish\Core\REST\Server\Values;
use eZ\Publish\Core\REST\Server\Exceptions;
use eZ\Publish\Core\REST\Server\Controller as RestController;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SectionService;
use eZ\Publish\API\Repository\Repository;

use eZ\Publish\API\Repository\Values\User\UserRoleAssignment;
use eZ\Publish\API\Repository\Values\User\UserGroupRoleAssignment;

use eZ\Publish\API\Repository\Exceptions\NotFoundException as APINotFoundException;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;

use eZ\Publish\Core\REST\Common\Exceptions\NotFoundException AS RestNotFoundException;
use eZ\Publish\Core\REST\Server\Exceptions\ForbiddenException;
use eZ\Publish\Core\REST\Common\Exceptions\NotFoundException;
use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * User controller
 */
class User extends RestController
{
    /**
     * User service
     *
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * Role service
     *
     * @var \eZ\Publish\API\Repository\RoleService
     */
    protected $roleService;

    /**
     * Content service
     *
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * Content service
     *
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    protected $contentTypeService;

    /**
     * Location service
     *
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * Section service
     *
     * @var \eZ\Publish\API\Repository\SectionService
     */
    protected $sectionService;

    /**
     * Repository
     *
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * Construct controller
     *
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \eZ\Publish\API\Repository\RoleService $roleService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\SectionService $sectionService
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct(
        UserService $userService,
        RoleService $roleService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        LocationService $locationService,
        SectionService $sectionService,
        Repository $repository )
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->locationService = $locationService;
        $this->sectionService = $sectionService;
        $this->repository = $repository;
    }

    /**
     * Loads a user for the given ID
     *
     * @param $userId
     *
     * @return \eZ\Publish\Core\REST\Server\Values\RestUser
     */
    public function loadUser( $userId )
    {
        $user = $this->userService->loadUser( $userId );

        $userContentInfo = $user->getVersionInfo()->getContentInfo();
        $userMainLocation = $this->locationService->loadLocation( $userContentInfo->mainLocationId );
        $contentType = $this->contentTypeService->loadContentType( $userContentInfo->contentTypeId );

        return new Values\CachedValue(
            new Values\RestUser(
                $user,
                $contentType,
                $userContentInfo,
                $userMainLocation,
                $this->contentService->loadRelations( $user->getVersionInfo() )
            ),
            array( 'locationId' => $userContentInfo->mainLocationId )
        );
    }

    /**
     * Create a new user group in the given group
     *
     * @param $groupPath
     *
     * @throws \eZ\Publish\Core\REST\Server\Exceptions\ForbiddenException
     * @return \eZ\Publish\Core\REST\Server\Values\CreatedUser
     */
    public function createUser( $groupPath )
    {
        $userGroupLocation = $this->locationService->loadLocation(
            $this->extractLocationIdFromPath( $groupPath )
        );
        $userGroup = $this->userService->loadUserGroup( $userGroupLocation->contentId );

        $userCreateStruct = $this->inputDispatcher->parse(
            new Message(
                array( 'Content-Type' => $this->request->headers->get( 'Content-Type' ) ),
                $this->request->getContent()
            )
        );

        try
        {
            $createdUser = $this->userService->createUser( $userCreateStruct, array( $userGroup ) );
        }
        catch ( InvalidArgumentException $e )
        {
            throw new ForbiddenException( $e->getMessage() );
        }

        $createdContentInfo = $createdUser->getVersionInfo()->getContentInfo();
        $createdLocation = $this->locationService->loadLocation( $createdContentInfo->mainLocationId );
        $contentType = $this->contentTypeService->loadContentType( $createdContentInfo->contentTypeId );

        return new Values\CreatedUser(
            array(
                'user' => new Values\RestUser(
                    $createdUser,
                    $contentType,
                    $createdContentInfo,
                    $createdLocation,
                    $this->contentService->loadRelations( $createdUser->getVersionInfo() )
                )
            )
        );
    }

    /**
     * Updates a user
     *
     * @param $userId
     *
     * @return \eZ\Publish\Core\REST\Server\Values\RestUser
     */
    public function updateUser( $userId )
    {
        $user = $this->userService->loadUser( $userId );

        $updateStruct = $this->inputDispatcher->parse(
            new Message(
                array(
                    'Content-Type' => $this->request->headers->get( 'Content-Type' ),
                    // @todo Needs refactoring! Temporary solution so parser has access to URL
                    'Url' => $this->request->getPathInfo()
                ),
                $this->request->getContent()
            )
        );

        if ( $updateStruct->sectionId !== null )
        {
            $section = $this->sectionService->loadSection( $updateStruct->sectionId );
            $this->sectionService->assignSection(
                $user->getVersionInfo()->getContentInfo(),
                $section
            );
        }

        $updatedUser = $this->userService->updateUser( $user, $updateStruct->userUpdateStruct );
        $updatedContentInfo = $updatedUser->getVersionInfo()->getContentInfo();
        $mainLocation = $this->locationService->loadLocation( $updatedContentInfo->mainLocationId );
        $contentType = $this->contentTypeService->loadContentType( $updatedContentInfo->contentTypeId );

        return new Values\RestUser(
            $updatedUser,
            $contentType,
            $updatedContentInfo,
            $mainLocation,
            $this->contentService->loadRelations( $updatedUser->getVersionInfo() )
        );
    }

    /**
     * Given user is deleted
     *
     * @param $userId
     *
     * @throws \eZ\Publish\Core\REST\Server\Exceptions\ForbiddenException
     * @return \eZ\Publish\Core\REST\Server\Values\NoContent
     */
    public function deleteUser( $userId )
    {
        $user = $this->userService->loadUser( $userId );

        if ( $user->id == $this->repository->getCurrentUser()->id )
        {
            throw new Exceptions\ForbiddenException( "Currently authenticated user cannot be deleted" );
        }

        $this->userService->deleteUser( $user );

        return new Values\NoContent();
    }

    /**
     * Loads a user by its remote ID
     *
     * @return \eZ\Publish\Core\REST\Server\Values\RestUser
     */
    public function loadUserByRemoteId()
    {
        $contentInfo = $this->contentService->loadContentInfoByRemoteId( $this->request->query->get( 'remoteId' ) );
        $user = $this->userService->loadUser( $contentInfo->id );
        $userLocation = $this->locationService->loadLocation( $contentInfo->mainLocationId );
        $contentType = $this->contentTypeService->loadContentType( $contentInfo->contentTypeId );

        return new Values\RestUser(
            $user,
            $contentType,
            $contentInfo,
            $userLocation,
            $this->contentService->loadRelations( $user->getVersionInfo() )
        );
    }

    public function getUserSubscription($userId)
    {
        #die(var_dump($this));
        $session = $this->request->getSession();
        $currentUser = $this->repository->getCurrentUser();
        return json_encode(array('response' => $currentUser));
                #$sessionName = $session->getName();
        #echo "<pre>";
        #var_dump($currentUser);
        #var_dump($sessionName);
        #var_dump($session);
        #echo "</pre>";
        #die();
    }

    /**
     * Creates a new session based on the credentials provided as POST parameters
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\UnauthorizedException If the login or password are incorrect or invalid CSRF
     * @return Values\UserSession|Values\Conflict
     */
    public function createSession()
    {
        /** @var $sessionInput \eZ\Publish\Core\REST\Server\Values\SessionInput */
        $sessionInput = $this->inputDispatcher->parse(
            new Message(
                array( 'Content-Type' => $this->request->headers->get( 'Content-Type' ) ),
                $this->request->getContent()
            )
        );
        $this->request->attributes->set( 'username', $sessionInput->login );
        $this->request->attributes->set( 'password', $sessionInput->password );

        try
        {
            $csrfToken = '';
            $csrfProvider = $this->container->get( 'form.csrf_provider', ContainerInterface::NULL_ON_INVALID_REFERENCE );
            $session = $this->request->getSession();
            if ( $session->isStarted() )
            {
                if ( $csrfProvider )
                {
                    $csrfToken = $this->request->headers->get( 'X-CSRF-Token' );
                    if (
                        !$csrfProvider->isCsrfTokenValid(
                            $this->container->getParameter( 'ezpublish_rest.csrf_token_intention' ),
                            $csrfToken
                        )
                    )
                    {
                        throw new UnauthorizedException( 'Missing or invalid CSRF token', $csrfToken );
                    }
                }
            }

            $authenticator = $this->container->get( 'ezpublish_rest.session_authenticator' );
            $token = $authenticator->authenticate( $this->request );
            // If CSRF token has not been generated yet (i.e. session not started), we generate it now.
            // This will seamlessly start the session.
            if ( !$csrfToken )
            {
                $csrfToken = $csrfProvider->generateCsrfToken(
                    $this->container->getParameter( 'ezpublish_rest.csrf_token_intention' )
                );
            }

            return new Values\UserSession(
                $token->getUser()->getAPIUser(),
                $session->getName(),
                $session->getId(),
                $csrfToken,
                !$token->hasAttribute( 'isFromSession' )
            );

        }
        // Already logged in with another user, this will be converted to HTTP status 409
        catch ( Exceptions\UserConflictException $e )
        {
            return new Values\Conflict();
        }
        catch ( AuthenticationException $e )
        {
            throw new UnauthorizedException( "Invalid login or password", $this->request->getPathInfo() );
        }
        catch ( AccessDeniedException $e )
        {
            throw new UnauthorizedException( $e->getMessage(), $this->request->getPathInfo() );
        }
    }

    /**
     * Refresh given session.
     *
     * @param string $sessionId
     *
     * @throws \eZ\Publish\Core\REST\Common\Exceptions\NotFoundException
     * @return \eZ\Publish\Core\REST\Server\Values\UserSession
     */
    public function refreshSession( $sessionId )
    {
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $this->request->getSession();
        $inputCsrf = $this->request->headers->get( 'X-CSRF-Token' );
        if ( !$session->isStarted() || $session->getId() != $sessionId || $session == null )
        {
            throw new RestNotFoundException( "Session not valid" );
        }

        return new Values\UserSession(
            $this->repository->getCurrentUser(),
            $session->getName(),
            $session->getId(),
            $inputCsrf,
            false
        );
    }

    /**
     * Deletes given session.
     *
     * @param string $sessionId
     * @return \eZ\Publish\Core\REST\Server\Values\NoContent
     * @throws RestNotFoundException
     */
    public function deleteSession( $sessionId )
    {
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $this->container->get( 'session' );
        if ( !$session->isStarted() || $session->getId() != $sessionId )
        {
            throw new RestNotFoundException( "Session not found: '{$sessionId}'." );
        }

        return new Values\DeletedUserSession(
            $this->container->get( 'ezpublish_rest.session_authenticator' )->logout( $this->request )
        );
    }

    /**
     * Extracts and returns an item id from a path, e.g. /1/2/58 => 58
     *
     * @param string $path
     *
     * @return mixed
     */
    private function extractLocationIdFromPath( $path )
    {
        $pathParts = explode( '/', $path );
        return array_pop( $pathParts );
    }
}
