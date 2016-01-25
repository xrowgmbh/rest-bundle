xrow RestBundle
====================

## Documentation

This is a bundle which creates an API for third party application. The data for the API are comming from your favorite CRM (salesforce, navision, and so on).

1. Create a class in your crm bundle and implements CRMPluginInterface. Here is the default [crm plugin class](https://github.com/xrowgmbh/rest-bundle/blob/master/CRM/CRMPlugin.php). Add the path to your crm plugin class in your app/config/config.yml:
    ```yml
    # app/config/config.yml

    xrow_rest:
        plugins:
            crmclass:   path\toYour\CRMPluginClass

2. Edit your ezpublish/config/config.yml. Add this configuration:
    ```yml
    # app/config/config.yml

    assetic:
        ...
        assets:
            ...
            xrowrest_js:
                inputs:
                    - %kernel.root_dir%/../vendor/xrow/rest-bundle/Resources/public/js/xrowrest.js
                output: js/xrowrest.js

    doctrine:
        orm:
            auto_mapping: true

2.1 Choose one of the OAuth2 bundles: FOSOAuthServerBundle (without OpenID Connect) or oauth2-server-bundle (with OpenID Connect)

2.1.1 For FOSOAuthServerBundle (https://github.com/FriendsOfSymfony/FOSOAuthServerBundle)
    ```yml
    # app/config/config.yml

    fos_oauth_server:
        db_driver: orm
        client_class:        xrow\restBundle\Entity\Client
        access_token_class:  xrow\restBundle\Entity\AccessToken
        auth_code_class:     xrow\restBundle\Entity\AuthCode
        refresh_token_class: xrow\restBundle\Entity\RefreshToken
        service:
            user_provider: xrowsso.platform.user.provider
            storage:       xrow_oauth_server.storage
            options:
                supported_scopes: user

2.1.2 For oauth2-server-bundle (https://github.com/bshaffer/oauth2-server-bundle)
    ```yml
    # app/config/service.yml

    services:
        oauth2.user_provider:
            class: xrow\restBundle\Provider\OAuth2UserProvider
            arguments:
                - "@service_container"
                - "doctrine.orm.entity_manager"
                - "security.encoder_factory"

    ```yml
    # app/config/parameters.yml
    parameters:
        oauth2.grant_type.user_credentials.class: xrow\restBundle\GrantType\UserCredentials
        oauth2.storage.user_credentials.class: xrow\restBundle\Storage\UserCredentials

## License

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE