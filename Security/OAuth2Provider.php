<?php

namespace xrow\restBundle\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use xrow\restBundle\Security\OAuth2Token;

class OAuth2Provider implements AuthenticationProviderInterface
{
    /**
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * 
     */
    public function __construct(UserProviderInterface $userProvider, ContainerInterface $container)
    {
        $this->userProvider  = $userProvider;
        $this->container  = $container;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        try {
            $tokenString = $token->getToken();
            if ($accessToken = $this->container->get('xrow_rest.api.helper')->verifyAccessToken($tokenString, 'OAuth2')) {
                $user = $this->userProvider->loadUserById($accessToken->getUserId());
                if ($user instanceof OAuth2UserCRM) {
                    $roles = (null !== $user) ? $user->getRoles() : array();

                    $authToken = new OAuth2Token($roles);
                    $authToken->setAuthenticated(true);
                    $authToken->setToken($tokenString);

                    if (null !== $user) {
                        $authToken->setUser($user);
                    }

                    return $authToken;
                }
                else {
                    var_dump('FALSCHER USER');
                }
            }
        } catch (OAuth2ServerException $e) {
            throw new AuthenticationException('OAuth2 authentication failed', 0, $e);
        }

        throw new AuthenticationException('OAuth2 authentication failed');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuth2Token;
    }
}
