<?php

namespace xrow\restBundle\Storage;

use OAuth2\Storage\UserCredentialsInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use OAuth2\ServerBundle\User\OAuth2UserInterface;
use OAuth2\ServerBundle\User\AdvancedOAuth2UserInterface;

class OAuth2UserCredentials implements UserCredentialsInterface
{
    private $em;
    private $userProvider;
    private $encoderFactory;
    public $container;
    public $user;

    public function __construct(EntityManager $entityManager, UserProviderInterface $userProvider, EncoderFactoryInterface $encoderFactory)
    {
        $this->em = $entityManager;
        $this->userProvider = $userProvider;
        $this->encoderFactory = $encoderFactory;
        $this->container = $this->userProvider->container;
        $this->user = null;
    }

    public function checkUserCredentials($username, $password)
    {
        $badRequest = '400 Bad Request';
        // Load user by username and password
        $user = $this->userProvider->loadUserFromCRM($username, $password);

        if (null !== $user) {
            if (is_string($user) && $user == 'NOTACTIVE')
                return array('error' => array($badRequest, 'invalid_grant', $this->container->get('translator')->trans("Your account has to be activated")));
            if (is_string($user) && strpos($user, 'LOCKOUT') !== false) {
                $errorText = explode("=>", $user);
                return array('error' => array($badRequest, 'invalid_grant', $errorText[1]));
            }
            if ($user->getId() === NULL) {
                $this->em->persist($user);
                $this->em->flush();
            }
            $this->user = $user;
        }
        else {
            return array('error' => array($badRequest, 'invalid_grant', $this->container->get('translator')->trans("Invalid username and password combination")));
        }
    }

    /**
     * @return
     * ARRAY the associated "user_id" and optional "scope" values
     * This function MUST return FALSE if the requested user does not exist or is
     * invalid. "scope" is a space-separated list of restricted scopes.
     * @code
     * return array(
     *     "user_id"  => USER_ID,    // REQUIRED user_id to be stored with the authorization code or access token
     *     "scope"    => SCOPE       // OPTIONAL space-separated list of restricted scopes
     * );
     * @endcode
     */
    public function getUserDetails($username)
    {
        return array(
            'user_id' => $this->user->getId(),
            'crmuserId' => $this->user->getCrmuserId(),
            'scope' => $this->user->getScope()
        );
    }
}
