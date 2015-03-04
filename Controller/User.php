<?php
/**
 * File containing the User controller class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace Xrow\Bundle\RestBundle\Controller;

use eZ\Publish\Core\REST\Common\Message;
use eZ\Publish\Core\REST\Server\Values;
use eZ\Publish\Core\REST\Server\Exceptions;
use eZ\Publish\Core\REST\Server\Controller as RestController;

use eZ\Publish\API\Repository\UserService;

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

    public function getUserSubscriptionsAction($userId)
    {
        die('drin');
        /*$user = $this->userService->loadUser( $userId );
    
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
        );*/
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
}