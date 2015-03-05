<?php

/*
* This file is part of the Sylius package.
*
* (c) Paweł Jędrzejewski
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace xrow\restBundle\OAuth;

use FOS\UserBundle\Model\UserInterface as FOSUserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider extends FOSUBUserProvider
{
    public function __construct(UserManagerInterface $userManager, array $properties)
    {
        $this->userManager = $userManager;
        $this->properties  = array_merge($this->properties, $properties);
        $this->accessor    = PropertyAccess::createPropertyAccessor();
    }
    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $username = $response->getUsername();

        $user = $this->userManager->findUserBy(array($this->getProperty($response) => $username));
        if (null === $user || null === $username) {
            throw new AccountNotLinkedException(sprintf("User '%s' not found.", $username));
        }

        return $user;
    }
}
