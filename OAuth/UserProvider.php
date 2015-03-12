<?php

namespace xrow\restBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\EntityUserProvider;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider extends EntityUserProvider
{
    public function __construct()
    #public function __construct(UserManagerInterface $userManager, array $properties)
    {
        #$this->userManager = $userManager;
        #$this->properties  = array_merge($this->properties, $properties);
        #$this->accessor    = PropertyAccess::createPropertyAccessor();
    }
    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        var_dump($response);
        die('loadUserByOAuthUserResponse in OAuth/UserProvider.php');
        $username = $response->getUsername();

        $configResolver = $this->getConfigResolver();
        // hier wird dann ein User aus dem CRM System geladen, sofert welches in xrow_rest_settings.plugins.crmclass gesetzt ist
        if($configResolver->hasParameter( 'xrow_rest_settings.plugins.crmclass' ))
        {
            $CRMClass = $configResolver->getParameter( 'xrow_rest_settings.plugins.crmclass' );
            $eZUserLoginName = $CRMClass::getAPIUser($response);
        }
        else
        {
            $eZUserLoginName = 'anonymous';
        }
        $user = $response->setApiUser( $this->userService->loadUserByLogin( $eZUserLoginName ) );
        if (null === $user || null === $username) {
            throw new Exception(sprintf("User '%s' not found.", $username));
        }

        return $user;
    }
}