<?php

namespace xrow\restBundle\Storage;

use OAuth2\OpenID\Storage\UserClaimsInterface as OpenIDUserClaimsInterface;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface as OpenIDAuthorizationCodeInterface;
use OAuth2\Storage\Memory as BaseMemory;
use OAuth2\Storage;

class OAuth2Memory extends BaseMemory implements OpenIDUserClaimsInterface, OpenIDAuthorizationCodeInterface, Storage\UserCredentialsInterface, Storage\AccessTokenInterface,
      Storage\ClientCredentialsInterface, Storage\RefreshTokenInterface, Storage\JwtBearerInterface, Storage\ScopeInterface, Storage\PublicKeyInterface
{
    public function __construct($params = array())
    {
        parent::__construct($params);
    }

    public function getPublicKey($client_id = null)
    {
        if (isset($this->keys[$client_id])) {
            if (file_exists($this->keys[$client_id]['public_key'])) {
                return openssl_get_publickey(file_get_contents($this->keys[$client_id]['public_key']));
            }
            return $this->keys[$client_id]['public_key'];
        }

        // use a global encryption pair
        if (isset($this->keys['public_key'])) {
            return $this->keys['public_key'];
        }

        return false;
    }

    public function getPrivateKey($client_id = null)
    {
        if (isset($this->keys[$client_id])) {
            if (file_exists($this->keys[$client_id]['private_key'])) {
                return openssl_get_privatekey(file_get_contents($this->keys[$client_id]['private_key']));
            }
            return $this->keys[$client_id]['private_key'];
        }
        // use a global encryption pair
        if (isset($this->keys['private_key'])) {
            return $this->keys['private_key'];
        }

        return false;
    }
}