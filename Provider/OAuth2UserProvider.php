<?php

namespace xrow\restBundle\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use Doctrine\ORM\EntityManager;
use eZ\Publish\Core\MVC\Symfony\Security\UserWrapped as eZUserWrapped;
use xrow\restBundle\Entity\OAuth2UserCRM;

class OAuth2UserProvider implements UserProviderInterface
{
    public $container;
    private $em;
    private $encoderFactory;
    private $crmPluginClassObject;

    const CLASSNAME = '\xrow\restBundle\Entity\OAuth2UserCRM';

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     */
    public function __construct(ContainerInterface $container, EntityManager $entityManager, EncoderFactoryInterface $encoderFactory)
    {
        $this->container = $container;
        $this->em = $entityManager;
        $this->encoderFactory = $encoderFactory;
        $this->crmPluginClassObject = $this->container->get('xrow_rest.crm.plugin');
    }

    /**
     * Gets CRM user with username and password
     *
     * @param string $username
     * @param string $password
     * @throws UsernameNotFoundException
     * @return \xrow\restBundle\Entity\OAuth2UserCRM (UserInterface)
     */
    public function loadUserFromCRM($username, $password)
    {
        $user = null;
        $crmUser = $this->crmPluginClassObject->loadUser(trim($username), trim($password));

        if ($crmUser !== null) {
            try {
                $user = $this->loadUserByUsername($crmUser['id']);
            } catch(UsernameNotFoundException $e) {
                $user = $this->createUser($crmUser, trim($password), array('ROLE_USER'), array('user', 'openid'));
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
     * @return \xrow\restBundle\Entity\OAuth2UserCRM (UserInterface)
     */
    public function loadUserByUsername($crmuserId)
    {
        $user = $this->em->getRepository(self::CLASSNAME)->findOneBy(array('crmuserId' => $crmuserId));

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('User with crmuserId "%s" not found.', $crmuserId));
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
     * @return \xrow\restBundle\Entity\OAuth2UserCRM (UserInterface)
     */
    public function loadUserById($id)
    {
        $user = $this->em->getRepository(self::CLASSNAME)->findOneBy(array('id' => $id));

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('User with id "%s" not found.', $id));
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
     * @return \xrow\restBundle\Entity\OAuth2UserCRM (UserInterface)
     */
    public function refreshUser(UserInterface $user)
    {
        // With InteractiveLoginEvent we get an eZ User but we would like to handle with our API user
        if ($user instanceof eZUserWrapped) {

            $repository = $this->container->get('ezpublish.api.repository');
            $repository->setCurrentUser($user->getApiUser());

            /*
             Maybe we would like to set an eZ User later than activate this part for user with contentId 165105
            $repository = $this->container->get('ezpublish.api.repository');
            $refreshedAPIUser = $repository->getUserService()->loadUser(165105);
            $originalUser->setAPIUser($refreshedAPIUser);
            $repository->setCurrentUser($refreshedAPIUser);
            */
        }
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }
        $refreshedUser = $this->loadUserByUsername($user->getCrmuserId());
        if (null === $refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with crmuserId %s not found', $user->getCrmuserId()));
        }
        return $refreshedUser;
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $class === 'xrow\\restBundle\\Entity\\OAuth2UserCRM';
    }

    /**
     * Creates a new user
     *
     * @param string $username
     * @param string $password
     * @param array $roles
     * @param array $scopes
     *
     * @return UserInterface
     */
    public function createUser($crmUser, $password, array $roles = array(), array $scopes = array())
    {
        $user = new OAuth2UserCRM($crmUser['id']);
        $user->setUsername(md5($crmUser['id']));
        $user->setRoles($roles);
        $user->setScopes($scopes);

        // Generate password
        $salt = $this->generateSalt();
        $password = $this->encoderFactory->getEncoder($user)->encodePassword($password, $salt);

        $user->setSalt($salt);
        $user->setPassword($password);

        // Store User
        $this->em->persist($user);
        $this->em->flush();
        // Workaround for getId == NULL (don't know why getId returns NULL)
        if ($user->getId() === NULL) {
            $connection = $this->em->getConnection();
            $query = "SELECT id 
                      FROM oauth_user_crm
                      WHERE crmuserId = '".$crmUser['id']."'";
            // Doctrine\DBAL\Driver\PDOStatement
            $statement = $connection->executeQuery($query);
            $result = $statement->fetch();
            if (is_array($result) && count($result) > 0) {
                $user->setId($result['id']);
            }
        }
        return $user;
    }

    /**
     * Creates a salt for password hashing
     *
     * @return A salt
     */
    protected function generateSalt()
    {
        return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }
}