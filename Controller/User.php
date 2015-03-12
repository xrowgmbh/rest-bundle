<?php
/**
 * File containing the User controller class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace xrow\restBundle\Controller;

use eZ\Publish\Core\REST\Common\Message;
use eZ\Publish\Core\REST\Server\Values;
use eZ\Publish\Core\REST\Server\Exceptions;
use eZ\Publish\Core\REST\Server\Controller as RestController;

/**
 * User controller
 */
class User extends RestController
{
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
}