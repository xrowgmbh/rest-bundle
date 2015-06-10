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
        var settings = {"client_id": data.client_id,
                        "client_secret": data.client_secret,
                        "tokenURL": "/oauth/v2/token",
                        "authURL": "/xrowapi/v1/auth",
                        "apiUserURL": "/xrowapi/v1/user",
                        "apiLogoutURL": "/xrowapi/v1/logout"},
            loginform_id = data.loginform_id,
            callbackFunctionIfTokenIsSet = data.callbackFunctionIfTokenIsSet;
        require(["jso/jso"], function(JSO) {
            var jsoObj = new JSO({
                client_id: settings.client_id,
                scopes: ["user"],
                authorization: settings.authURL
            });
            JSO.enablejQuery($);
            if(callbackFunctionIfTokenIsSet != '') {
               if (typeof window[callbackFunctionIfTokenIsSet] == "function" ) {
                   var token = jsoObj.checkToken();
                   if(typeof token === "object" && token !== null) {
                       if(token.access_token) {
                           window[callbackFunctionIfTokenIsSet](jsoObj, settings);
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
                            window.location = settings.apiUserURL;
                        } else if(typeof response === "string"){
                            var queryHash = "#" + response.split("?"); 
                            jsoObj.callback(queryHash, function(token){
                                window.location = settings.apiUserURL;
                            });
                        }
                    });
                });
            });

            function sfLoginForm($form, callback){
                var request = {"grant_type": "password",
                               "scope": "user"};
                $.each($form.serializeArray(), function(i, field) {
                    request[field.name] = field.value;
                });
                request.client_id = settings.client_id;
                request.client_secret = settings.client_secret;
                $.ajax({
                    type    : 'post',
                    url     : settings.tokenURL,
                    data    : request
                }).done(function (requestData) {
                    if(typeof requestData.access_token != "undefined") {
                        $.ajax({
                            type    : 'post',
                            url     : settings.authURL+'?access_token='+requestData.access_token,
                            async   : false
                        }).done(function (data) {
                            jsoObj.getToken(function(data) {
                                callback(data);
                            }, requestData);
                        });
                    } else {
                        if(typeof requestData.responseJSON != "undefined") {
                            if (typeof requestData.responseJSON.error_description != "undefined") 
                                alert(requestData.responseJSON.error_description);
                        }
                        else
                            window.console.log("An unexpeded error occured xrjs0.");
                    }
                }).fail(function (jqXHR) {
                    if(typeof jqXHR.responseJSON != "undefined") {
                        if (typeof jqXHR.responseJSON.error_description != "undefined") 
                            alert(jqXHR.responseJSON.error_description);
                    }
                    else
                        window.console.log("An unexpeded error occured: " + jqXHR.statusText + ", HTTP Code " + jqXHR.status + ":xrjs1.");
                });
            }
        });
    } else {
        window.console.log("Please set oauth_client_id, oauth_client_secret and oauth_loginform_id in parameters.yml for xrowrest.js.");
    }
}).fail(function (jqXHR) {
    window.console.log("An unexpeded error occured: " + jqXHR.statusText + ", HTTP Code " + jqXHR.status + ":xrjs2.");
});