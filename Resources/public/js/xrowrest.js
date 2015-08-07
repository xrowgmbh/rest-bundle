/**
 * get parameters via ajax for set our js oauth2 client
 * you have to define client_id, client_secret and loginform id for your loginform, to get here username and password
 * -> parameters.yml:
 *        oauth_client_id: xyz
          oauth_client_secret: xyz
 * 
 * you can define a callback function after generating and saving an access token
 * -> parameters.yml
 *        oauth_callback_function_if_token_is_set: logoutUser
 */
if (typeof oa_params_cl != "undefined" && typeof oa_params_clsc != "undefined" && typeof oa_params_clba != "undefined") {
    var settings = {"client_id": oa_params_cl,
                    "client_secret": oa_params_clsc,
                    "baseURL": oa_params_clba,
                    "tokenURL": "/oauth/v2/token",
                    "authURL": "/xrowapi/v1/auth",
                    "apiSessionURL": "/xrowapi/v1/session",
                    "apiLogoutURL": "/xrowapi/v1/logout"},
        callbackFunctionIfTokenIsSet = '';
    if (typeof callbackFunctionIfToken != "undefined")
        callbackFunctionIfTokenIsSet = callbackFunctionIfToken;
    var jsoObj = new JSO({
        client_id: settings.client_id,
        authorization: settings.baseURL+settings.authURL,
        default_lifetime: false,
        providerID: "xrowapi/v1",
        scopes: ["user"],
        debug: true
    });
    JSO.enablejQuery($);
    var token = jsoObj.checkToken();
    if (token !== null && typeof token.access_token != 'undefined') {
        if(callbackFunctionIfTokenIsSet != '') {
            if (typeof window[callbackFunctionIfTokenIsSet] == "function") {
                window[callbackFunctionIfTokenIsSet](jsoObj, settings, token);
            }
        }
    }
    else {
        if(callbackFunctionIfTokenIsSet != '') {
            if (typeof window[callbackFunctionIfTokenIsSet] == "function") {
               window[callbackFunctionIfTokenIsSet](jsoObj, settings);
           }
       }
    }
    /**
     * you need a form with class use-api-logn
     * and two login fields: username and password
     * you can also define a redirect url after the login in your template like this
     * <input type="hidden" value="/redirect/onthis/server/with/ssl" data-protocol="https" />
     */
    $(document).ready(function(){
        if ($('form.use-api-login').length) {
            $('form.use-api-login').each(function() {
                $(this).submit(function(e){
                    e.preventDefault();
                    var loginForm = $(this),
                        errorOutputBoxId = loginForm.attr('id')+'-error';
                        counterGetToken = 0,
                        dataArray = {'form': loginForm,
                                     'settings': settings,
                                     'jsoObj': jsoObj};
                    restLoginForm(dataArray, function(getTokenData){
                        if (typeof getTokenData.error != 'undefined') {
                            if ($('#'+errorOutputBoxId).length) {
                                $('#'+errorOutputBoxId).text(getTokenData.error).show();
                            }
                            else {
                                window.console.log(getTokenData.error);
                            }
                        }
                        else if (typeof getTokenData === "string") {
                            var queryHash = "#" + getTokenData.split("?"); 
                            jsoObj.callback(queryHash, false);
                        }
                        if (counterGetToken == 0) {
                            counterGetToken++;
                            var token = jsoObj.checkToken();
                            if (token !== null) {
                                if (typeof token.access_token != "undefined") {
                                    var redirectAfterApiLoginObject = loginForm.find('input[name="redirectAfterApiLogin"]');
                                    if (redirectAfterApiLoginObject.length) {
                                        var redirectAfterApiLogin = redirectAfterApiLoginObject.val();
                                        // if value of redirect does not have /
                                        if (!redirectAfterApiLogin.match(/^http/) && !redirectAfterApiLogin.match(/^\//))
                                            redirectAfterApiLogin = '/'+redirectAfterApiLogin;
                                        // <input type="hidden" value="/redirect/onthis/server/with/ssl" data-protocol="https" />
                                        if (redirectAfterApiLoginObject.data('protocol') && !redirectAfterApiLogin.match(/^http/))
                                            redirectAfterApiLogin = redirectAfterApiLoginObject.data('protocol')+'//'+document.location.hostname+redirectAfterApiLogin;
                                        // <input type="hidden" value="http(s)://redirect-to-another-server.com/with/protocol" data-protocol="http(s)" />
                                        else if (redirectAfterApiLoginObject.data('protocol') && redirectAfterApiLogin.match(/^http/)) {
                                            // <input type="hidden" value="http://redirect-to-another-server.com/with/protocol" data-protocol="https" />
                                            if (redirectAfterApiLogin.match(/^https:/) && redirectAfterApiLoginObject.data('protocol') != 'https')
                                                redirectAfterApiLogin = redirectAfterApiLogin.replace(/^https:/, 'http:');
                                            // <input type="hidden" value="https://redirect-to-another-server.com/with/protocol" data-protocol="http" />
                                            else if (redirectAfterApiLogin.match(/^http:/) && redirectAfterApiLoginObject.data('protocol') != 'http')
                                                redirectAfterApiLogin = redirectAfterApiLogin.replace(/^http:/, 'https:');
                                        }
                                        // <input type="hidden" value="/redirect/onthis/server" />
                                        if (!redirectAfterApiLogin.match(/^http/))
                                            redirectAfterApiLogin = document.location.protocol+'//'+document.location.hostname+redirectAfterApiLogin;
                                        window.location.href = redirectAfterApiLogin;
                                    }
                                    else {
                                        location.reload();
                                    }
                                }
                            }
                        }
                    });
                });
            });
        }
    });
} else {
    window.console.log("Please set oauth_client_id, oauth_client_secret in parameters.yml for xrowrest.js.");
}

function restLoginForm(dataArray, callback){
    var request = {"grant_type": "password",
                   "scope": "user"},
        form = dataArray.form,
        settings = dataArray.settings,
        jsoObj = dataArray.jsoObj;
    jsoObj.wipeTokens();
    $.each(form.serializeArray(), function(i, field) {
        request[field.name] = field.value;
    });
    request.client_id = settings.client_id;
    request.client_secret = settings.client_secret;
    // Request 1 --- AccessToken Request
    $.ajax({
        type       : 'POST',
        xhrFields  : {
            withCredentials: true
        },
        crossDomain: true,
        url        : settings.baseURL+settings.tokenURL,
        data       : request
    }).done(function (requestData) {
        if (typeof requestData != "undefined") {
            if (typeof requestData.access_token != "undefined") {
                // Request 2 --- Authenticate Request
                $.ajax({
                    type       : 'GET',
                    xhrFields  : {
                        withCredentials: true
                    },
                    crossDomain: true,
                    url        : settings.baseURL+settings.authURL+"?access_token="+requestData.access_token
                }).done(function (authRequest) {
                    if (authRequest.result !== null) {
                        document.cookie = authRequest.result.session_name+"="+authRequest.result.session_id+"; path=/";
                        jsoObj.getToken(function(data) {
                            callback(data);
                        }, requestData);
                    }
                });
            } else {
                if (typeof requestData.error_description != "undefined")
                    var error = {'error': requestData.error_description};
                else if(typeof requestData.responseJSON != "undefined" && typeof requestData.responseJSON.error_description != "undefined")
                    var error = {'error': requestData.responseJSON.error_description};
                else
                    var error = {'error': 'An unexpeded error occured xrjs0.'};
                callback(error);
            }
        }
    }).fail(function (jqXHR) {
        if(typeof jqXHR.responseJSON != "undefined" && typeof jqXHR.responseJSON.error_description != "undefined")
            var error = {'error': jqXHR.responseJSON.error_description};
        else
            var error = {'error': 'An unexpeded error occured: ' + jqXHR.statusText + ', HTTP Code ' + jqXHR.status + ':xrjs1.'};
        callback(error);
    });
};
function restLogout(settings, jsoObj, redirectURL){
    $.ajax({
        type    : 'DELETE',
        url     : settings.apiLogoutURL
    }).done(function (logoutRequest) {
        if (typeof logoutRequest != "undefined" && typeof logoutRequest.session_name != "undefined" && logoutRequest.session_name != '')
            document.cookie = logoutRequest.session_name+'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        jsoObj.wipeTokens();
        if (redirectURL && redirectURL != '')
            window.location.href = redirectURL;
        else
            location.reload();
    });
};