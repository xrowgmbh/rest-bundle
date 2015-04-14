xrow RestBundle
====================

## Documentation

This is a bundle which creates an API for third party application. The data for the API are comming from your favorite CRM (salesforce, navision, and so on).

1. Step:
Create a class in your crm bundle and implements CRMPluginInterface. Here is the default [crm plugin class](https://github.com/xrowgmbh/rest-bundle/blob/master/CRM/CRMPlugin.php).
Add the path to your crm plugin class in your ezpublish/config/config.yml:
xrow_rest:
    plugins:
        crmclass:   path\toYour\CRMPluginClass

2. Install https://github.com/jrburke/r.js

3. Edit your ezpublish/config/config.yml. Add this configuration:

assetic:
    ...
    assets:
        ...
        xrowrest_js:
            inputs:
                - %kernel.root_dir%/../vendor/xrow/rest-bundle/Resources/public/js/xrowrest.js
            output: js/xrowrest.js

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

doctrine:
    orm:
        auto_mapping: true

xrow_rest:
    plugins:
        crmclass:   path\ToYourCRM\CRWMClassPlugin

hearsay_require_js:
    require_js_src: /bundles/xrowrest/js/require.js
    base_directory: /lib/node_modules/requirejs/bin/
    base_url: js
    paths:
        jso: 
            location: @xrowRestBundle/Resources/public/js/src
    optimizer:
        path: /lib/node_modules/requirejs/bin/r.js

## License

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE