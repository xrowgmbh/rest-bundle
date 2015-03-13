<?php

namespace xrow\restBundle\Model;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;

class Client extends BaseClient
{
    public function getPublicId()
    {
        return $this->randomId;
    }
}
