<?php

namespace xrow\restBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;

class UserProvider implements UserProviderInterface
{
    public $userRepository;
    protected $container;
    protected $crmPluginClassObject;

    /**
     * 
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Doctrine\Common\Persistence\ObjectRepository $userRepository
     */
    public function __construct(ContainerInterface $container, ObjectRepository $userRepository){
        $this->container = $container;
        $crmClassName = $this->container->getParameter('xrow_rest.plugins.crmclass');
        $this->crmPluginClassObject = new $crmClassName();
        $this->userRepository = $userRepository;
        $this->crmPluginClassObject->connect($this->container);
    }

    public function loadUserFromCRM($username, $password)
    {
        try {
            $user = $this->crmPluginClassObject->loadUser(trim($username), trim($password), $this->userRepository);
        } catch (NoResultException $e) {
            $message = sprintf(
                'Unable to find an active api user object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0, $e);
        }

        return $user;
    }

    /**
     * this function is not in use for our authorization
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::loadUserByUsername()
     */
    public function loadUserByUsername($username)
    {
        try {
            $user = $this->userRepository->findOneBy(array('username' => $username));
        } catch (NoResultException $e) {
            $message = sprintf(
                'Unable to find an active api user object identified by "%s".',
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
        $refreshedUser = $this->userRepository->find($user->getId());
        if (null === $refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with id %s not found', json_encode($user->getId())));
        }

        return $refreshedUser;
    }

    public function supportsClass($class)
    {
        return $this->userRepository->getClassName() === $class
        || is_subclass_of($class, $this->userRepository->getClassName());
    }
}