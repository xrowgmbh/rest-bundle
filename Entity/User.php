<?php

namespace xrow\restBundle\Entity;

use Symfony\Component\Security\Core\User\User as SecurityUser;
use Doctrine\ORM\Mapping as ORM;

/**
* xrow\restBundle\Entity\User
*
* @ORM\Table(name="api_Users")
* @ORM\Entity(repositoryClass="xrow\restBundle\Repository\UserRepository")
*/
class User extends SecurityUser
{
    /**
    * @ORM\Column(type="string", length=25, unique=true)
    */
    private $crmuserId;

    public function __construct($crmuserId)
    {
        $this->crmuserId = $crmuserId;
    }

    /**
    * @inheritDoc
    */
    public function getCrmuserId()
    {
        return $this->crmuserId;
    }

    public function setCrmuserId($crmuserId)
    {
        $this->crmuserId = $crmuserId;
    }

    /**
    * @inheritDoc
    */
    public function getRoles()
    {
        return array('ROLE_OAUTH_USER', 'ROLE_USER');
    }

    public function getCurrency()
    {
        return 'EUR';
    }
}