<?php
/**
 * File containing the CRM Plugin controller class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace xrow\restBundle\Controller;

/**
 * User controller
 */
class CRMPlugin extends RestController
{
    public function getUserSubscription($userId)
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
}