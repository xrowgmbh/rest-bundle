<?php

namespace xrow\restBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class OAuth2Token extends AbstractToken
{
    /**
     * @var string
     */
    protected $token;


    /**
     * Sets the user in the token.
     *
     * @param string|object $user The user
     *
     */
    public function setUser($user)
    {
        if ($user instanceof UserInterface) {
            if (null === $this->user) {
                $changed = false;
            } elseif ($this->user instanceof UserInterface) {
                if (!$user instanceof UserInterface) {
                    $changed = true;
                } else {
                    $changed = $this->hasUserChanged($user);
                }
            } elseif ($user instanceof UserInterface) {
                $changed = true;
            } else {
                $changed = (string) $this->user !== (string) $user;
            }

            if ($changed) {
                $this->setAuthenticated(false);
            }

            $this->user = $user;
        }
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getCredentials()
    {
        return $this->token;
    }
}