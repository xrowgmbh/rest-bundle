xrow RestBundle Angular 2 OpenID Connect Client
===============================================

## Documentation

This is a OpenID Connect client for login an user and creating a jwt token on selected domains.

1. For developing please edit the .ts-files in Resources/public/js/angular/openIDConnect/app and compile with
- cd vendor/xrow/rest-bundle/Resources/public/js/angular/openIDConnect
- sudo npm install              /* add node_modules to your app root folder */
- sudo npm run build            /* Remove build folder, compile ts to js and copy them to new build-folder */
If you would like to develope without executing "clean and build" you can use "sudo npm run watch" and you will get your changes on the fly.
For developing without expire everytime the varnish please rename .htaccessDISABLED to .htaccess and restart your varnish. From now on varnish do not cache your files.

2. For get the prod version of your code:
- sudo npm run prod             /* Remove node_modules */