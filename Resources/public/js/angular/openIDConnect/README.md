xrow RestBundle Angular 2 OpenID Connect Client
===============================================

## Documentation

This is a OpenID Connect client with using a jwt token on selected domains.

1. For developing please edit the .ts-files in Resources/public/js/angular/openIDConnect/app and compile with
- cd vendor/xrow/rest-bundle/Resources/public/js/angular/openIDConnect
- npm install              // Add node_modules to your app root folder.
- npm run build-dev        // Remove build folder, compile ts to js and copy them to new build-folder, rename .htaccessDISABLED to .htaccess to "disable" caching on varnish for your angular project...
1.1. If you would like to develope without executing always "npm run build-xyz" you can use "npm run watch" and you will get your changes on the fly.

2. For get the prod version of your code:
- npm run build-prod       /* See "npm run build-dev" plus removing node_modules and renaming .htaccess to .htaccessDISABLED. */

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
     jwtProviderId="yourCustomLocalStorageTokenName">Loading...</angular-sso-login-app>

4. For define your own template with the login form you have to set this in your html head:
<script>
    var pathToLoginTemplate = '/bundles/yourbundle/pathtologin.html';
</script>

Please ignore some errors regarding to included JavaScript variables like oauthSettings or jwtProviderId for example.