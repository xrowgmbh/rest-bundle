xrow RestBundle
====================

This is a bundle which creates an API for third party application. The data for the API are comming from your favorite CRM (salesforce, navision, and so on).

## Installation

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
                - "@doctrine.orm.entity_manager"
                - "@security.encoder_factory"
        oauth2.openid.storage.authorization_code:
            class: xrow\restBundle\Storage\OAuth2Memory
            arguments:
                - {client_credentials: {%oauth2.client_id%: {client_secret: %oauth2.client_secret%}}, keys: {%oauth2.client_id%: {public_key: %oauth2.public_key%, private_key: %oauth2.private_key%}}}
        oauth2.openid.grant_type.authorization_code:
            class: OAuth2\OpenID\GrantType\AuthorizationCode
            arguments:
                - "@oauth2.openid.storage.authorization_code"
        oauth2.grant_type.user_credentials:
            class: xrow\restBundle\GrantType\OAuth2UserCredentials
            arguments:
                - "@oauth2.storage.user_credentials"
                - "@service_container"
        oauth2.server:
            class: "%oauth2.server.class%"
            arguments:
                - ["@oauth2.storage.client_credentials", "@oauth2.storage.access_token", "@oauth2.openid.storage.authorization_code", "@oauth2.storage.user_credentials", "@oauth2.storage.refresh_token", "@oauth2.storage.scope"]
                - {refresh_token_lifetime: 15552000, use_openid_connect: true, issuer: %oauth_baseurl%, use_jwt_access_tokens: true, always_issue_new_refresh_token: true}
                - {authorization_code: "@oauth2.openid.grant_type.authorization_code", refresh_token: "@oauth2.grant_type.refresh_token", user_credentials: "@oauth2.grant_type.user_credentials"}

    ```yml
    # app/config/parameters.yml
    parameters:
        oauth2.grant_type.user_credentials.class: xrow\restBundle\GrantType\UserCredentials
        oauth2.storage.user_credentials.class: xrow\restBundle\Storage\UserCredentials

3. Set the right route in your app/config/routing.yml

3.1 For oauth2-server-bundle (https://github.com/bshaffer/oauth2-server-bundle)
    ```yml
    # app/config/routing.yml

    xrow_rest_api:
        resource: "@xrowRestBundle/Controller/ApiController.php"
        type:     annotation
        prefix:   /xrowapi/v1

3.2 For oauth2-server-bundle (https://github.com/bshaffer/oauth2-server-bundle)
    ```yml
    # app/config/routing.yml

    xrow_rest_apiV2:
        resource: "@xrowRestBundle/Controller/ApiControllerV2.php"
        type:     annotation
        prefix:   /xrowapi/v2

3.3 Or both if you would like to use both on the same time

4. Add the page_head_script.html.twig to html head-tag and page_footer_script.html.twig to your footer (!!!important: after you load the tag <angular-sso-login-app>)

{% block footer %}
    {% include 'wuvaboshopBundle::page_footer_script.html.twig' %}
{% endblock %}

## License

This bundle is under the MIT license.
See the complete [license](Resources/meta/LICENSE) in the bundle.

