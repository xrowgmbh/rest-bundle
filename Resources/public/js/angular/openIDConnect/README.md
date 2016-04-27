xrow RestBundle Angular 2 OpenID Connect Client
===============================================

## Documentation

This is a OpenID Connect client for login an user and creating a jwt token on selected domains.

1. For developing please edit the .ts-files in Resources/public/js/angular/openIDConnect/app and compile with
- cd vendor/xrow/rest-bundle/Resources/public/js/angular/openIDConnect
- sudo npm install              /* add node_modules to your app root folder */
- sudo npm run build-dev        /* Remove build folder, compile ts to js and copy them to new build-folder */
If you would like to develope without executing "clean and build" you can use "sudo npm run watch" and you will get your changes on the fly.
For developing without expire everytime the varnish please rename .htaccessDISABLED to .htaccess and restart your varnish. From now on varnish do not cache your files.

2. For get the prod version of your code:
- sudo npm run build-prod       /* See "sudo npm run build-dev" plus removing node_modules. Now you can't execute any gulp or typescript commands. */

3. Add this tag to your template for loading a login form:
<angular-sso-login-app>Loading...</angular-sso-login-app>

You could also add this attributes to your tag for custom values:
- errorLoginEmptyfields
- buttonText
- buttonWaitingText
- jwtProviderId

like here:
<angular-sso-login-app
     errorLoginEmptyfields="Your custom error output for empty fields" 
     buttonText="Your custom button submit text" 
     buttonWaitingText="Please wait custom text..." 
     jwtProviderId="yourCustomLocaleStorageTokenName">Loading...</angular-sso-login-app>

4. For define your own template with the login form you have to set this in your html head:
<script>
    var pathToLoginTemplate = '/bundles/yourbundle/pathtologin.html';
</script>

Please ignore some errors regarding to included JavaScript variables like oauthSettings or jwtProviderId for example.