<?php

namespace xrow\restBundle\Entity;

use OAuth2\ServerBundle\Entity\User as BaseUser;
use OAuth2\ServerBundle\User\AdvancedOAuth2UserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oauth_user_crm")
 * @ORM\Entity
 */
class OAuth2UserCRM extends BaseUser implements AdvancedOAuth2UserInterface
{
    /**
     * @ORM\Column(type="integer", nullable=true, columnDefinition="INT AUTO_INCREMENT UNIQUE")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    protected $crmuserId;

    public function __construct($crmuserId)
    {
        $this->crmuserId = $crmuserId;
    }

    /**
     * Get Id
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set Id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get CRM UserId
     */
    public function getCrmuserId()
    {
        return $this->crmuserId;
    }

    /**
     * Set CRM UserId
     */
    public function setCrmuserId($crmuserId)
    {
        $this->crmuserId = $crmuserId;
    }

    /**
     * Require for Sylius
     * @return string
     */
    public function getCurrency()
    {
        return 'EUR';
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return true;
    }

    public function __toString()
    {
        return $this->crmuserId;
    }
}