/**
 * get parameters via ajax for set our js oauth2 client
 * you have to define client_id, client_secret and loginform id for your loginform, to get here username and password
 * -> parameters.yml:
 *        oauth_client_id: 1_49cgf41l9u80wo0g0sggw80wk8cc0cwsss8skk0cs0kcg8so40
          oauth_client_secret: 5xltt114rhs8o0c0sc088wkccg4o0ww04sk004kg8wgkos4w8s
          oauth_loginform_id: sfloginform
 * 
 * you can define a callback function after generating and saving an access token
 * -> parameters.yml
 *        oauth_callback_function_if_token_is_set: logoutUser
 */
$.ajax({
    type    : 'get',
    url     : '/getparams/client',
}).done(function (data) {
    // if required data is set
    if (typeof data.client_id != "undefined" && typeof data.client_secret != "undefined" && typeof data.loginform_id != "undefined") {
        var client_id = data.client_id,
            client_secret = data.client_secret,
            loginform_id = data.loginform_id,
            callbackFunctionIfTokenIsSet = data.callbackFunctionIfTokenIsSet,
            tokenURL = "/oauth/v2/token",
            authURL = "/xrowapi/v1/auth",
            apiUserURL = "/xrowapi/v1/user";
        require(["jso/jso"], function(JSO) {
            var jsoObj = new JSO({
                client_id: client_id,
                scopes: "user",
                authorization: authURL
            });
            JSO.enablejQuery($);
            if(callbackFunctionIfTokenIsSet != '') {
                if(typeof eval('callbackFunctionIfTokenIsSet') == "function") {
                    var token = jsoObj.checkToken();
                    if(typeof token === "object" && token !== null) {
                        if(token.access_token) {
                            eval('callbackFunctionIfTokenIsSet('+jsoObj+')');
                        }
                    }
                }
            }
            /**
             * you need a form with id sfloginform 
             * and two login fields: username and password
             */
            $(document).ready(function(){
                $('form#'+loginform_id).submit( function( e ){
                    e.preventDefault();
                    sfLoginForm($(this), function(response){
                        if(typeof response === "object"){
                            window.location = apiUserURL;
                        } else if(typeof response === "string"){
                            var queryHash = "#" + response.split("?"); 
                            jsoObj.callback(queryHash, function(token){
                                window.location = apiUserURL;
                            });
                        }
                    });
                });
            });

            function sfLoginForm($form, callback){
                var request = {"grant_type": "password",
                               "scope": "user"};
                $.each( $form.serializeArray(), function(i, field) {
                    request[field.name] = encodeURIComponent(field.value);
                });
                request.client_id = client_id;
                request.client_secret = client_secret;
                window.console.log(request);
                $.ajax({
                    type    : 'post',
                    url     : tokenURL,
                    data    : request
                }).done(function (requestData) {
                    jsoObj.getToken(function(data) {
                        callback( data );
                    }, requestData);
                });
            }
        });
    } else {
        alert("Please set oauth_client_id, oauth_client_secret and oauth_loginform_id in parameters.yml for xrowrest.js.");
    }
}).fail(function (jqXHR, textStatus) {
    alert("An unexpeded error occured in xrowrest.js during get parameters via ajax.");
});