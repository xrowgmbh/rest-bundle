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
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \xrow\restBundle\CRM\CRMPluginInterface
     */
    protected $crmPluginClassObject;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Doctrine\Common\Persistence\ObjectRepository $userRepository
     */
    public function __construct(ContainerInterface $container){
        $this->container = $container;
        $this->crmPluginClassObject = $this->container->get('xrow_rest.crm.plugin');
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->userRepository = $this->em->getRepository('\xrow\restBundle\Entity\User');
    }

    /**
     * Get user with username and password
     * 
     * @param string $username
     * @param string $password
     * @throws UsernameNotFoundException
     * @return \xrow\restBundle\Entity\User
     */
    public function loadUserFromCRM($username, $password, $openIdConnect = false)
    {
        $user = null;
        $crmUser = $this->crmPluginClassObject->loadUser(trim($username), trim($password));
        if ($crmUser !== null) {
            try {
                $user = $this->loadUserByUsername($crmUser['id']);
            } catch(UsernameNotFoundException $e) {
                $user = $this->createUser($crmUser);
            }
        }
        return $user;
    }

    /**
     * Loads the user for the given crmuserId.
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $crmuserId The CRM user id
     * @throws UsernameNotFoundException if the user is not found
     * @return UserInterface
     */
    public function loadUserByUsername($crmuserId)
    {
        $user = $this->userRepository->findOneBy(array('crmuserId' => $crmuserId));
        if ($user === null) {
            $message = sprintf(
                'Unable to find an active api user object identified by "%s".',
                $crmuserId
            );
            throw new UsernameNotFoundException($message);
        }

        return $user;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     * @throws UnsupportedUserException if the account is not supported
     * @throws UsernameNotFoundException if user not found
     *
     * @return UserInterface
     */
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

    /**
     * Creates a new user
     *
     * @param array $crmUser
     * 
     * @return UserInterface
     */
    public function createUser($crmUser)
    {
        $user = new \xrow\restBundle\Entity\User($crmUser['id']);
        $user->setUsername(md5($crmUser['id']));

        // Store User
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function supportsClass($class)
    {
        return $this->userRepository->getClassName() === $class
        || is_subclass_of($class, $this->userRepository->getClassName());
    }
}