<?php

namespace xrow\restBundle\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use xrow\restBundle\Repository\UserRepository;
use xrow\restBundle\CRM\CRMPlugin;

class UserProvider implements UserProviderInterface
{
    protected $userRepository;
    protected $crmPlugin;

    public function __construct(UserRepository $userRepository, CRMPlugin $crmPlugin){
        $this->userRepository = $userRepository;
        $this->crmPlugin = $crmPlugin->crmPluginClass;
    }

    public function loadUserFromCRM($username, $password)
    {
        $user = $this->crmPlugin->loadUser($username, $password);
        die(var_dump($password));
    }

    /**
     * this function is not in use for our authorization
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::loadUserByUsername()
     */
    public function loadUserByUsername($username)
    {
        try {
          $user = null;
      } catch (NoResultException $e) {
          $message = sprintf(
              'Unable to find an active admin AcmeDemoBundle:User object identified by "%s".',
              $username
          );
          throw new UsernameNotFoundException($message, 0, $e);
      }

      return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->userRepository->find($user->getId());
    }

    public function supportsClass($class)
    {
        return $this->userRepository->getClassName() === $class
        || is_subclass_of($class, $this->userRepository->getClassName());
    }
}