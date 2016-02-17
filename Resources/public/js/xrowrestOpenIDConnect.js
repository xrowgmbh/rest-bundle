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
if (typeof oauthSettings != "undefined" && typeof oauthSettings.client_id != "undefined" && typeof oauthSettings.baseURL != "undefined") {
    var jsoObj = new JSO({
        client_id: oauthSettings.client_id,
        authorization: oauthSettings.baseURL+oauthSettings.openIDConnectURL,
        default_lifetime: false,
        providerID: "xrowapi",
        scopes: ["user", "openid"],
        debug: false
    });
    JSO.enablejQuery($);
    var token = jsoObj.checkToken();
    if (token !== null && typeof token.access_token != 'undefined') {
        if(typeof callbackFunctionIfToken != "undefined" && typeof window[callbackFunctionIfToken] == "function") {
            window[callbackFunctionIfToken](jsoObj, oauthSettings, token);
        }
    }
    else if(typeof callbackFunctionIfToken != "undefined" && typeof window[callbackFunctionIfToken] == "function") {
        // If we set cookie via OpenID Connect iframe, we will set here the localStorageToken
        $.ajax({
            type       : 'GET',
            xhrFields  : {
                withCredentials: true,
            },
            crossDomain: true,
            url        : oauthSettings.baseURL+'/xrowapi/v2/storage',
            cache:     false
        }).done(function (storageRequest, textStatus, jqXHR) {
            if (typeof storageRequest != 'undefined' && typeof storageRequest.result != 'undefined' && storageRequest.result.session_state != 'undefined') {
                var requestData = {'client_id': oauthSettings.client_id,
                                   'access_token': storageRequest.result.session_state,
                                   'refresh_token': storageRequest.result.refresh_token,
                                   'response_type': 'token'};
                jsoObj.getToken(function(tokenData) {
                    if (typeof tokenData === "string") {
                        var queryHash = "#" + tokenData.split("?");
                        jsoObj.callback(queryHash, false);
                        var token = jsoObj.checkToken();
                        window[callbackFunctionIfToken](jsoObj, oauthSettings, token);
                    }
                }, requestData);
                if (snDomains.length > 0) {
                    var sesionCookieIsSet = JSON.parse(localStorage.getItem('xrowOIC'));
                    if (!sesionCookieIsSet) {
                        localStorage.setItem('xrowOIC', JSON.stringify(snDomains));
                    }
                }
            }
            else {
                window[callbackFunctionIfToken](jsoObj, oauthSettings, token);
            }
        }).fail(function (){
            window[callbackFunctionIfToken](jsoObj, oauthSettings, token);
        });
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
                        errorOutputBoxId = loginForm.attr('id')+'-error',
                        successOutputBoxId = loginForm.attr('id')+'-success',
                        counterGetToken = 0,
                        dataArray = {'form': loginForm,
                                     'oauthSettings': oauthSettings,
                                     'jsoObj': jsoObj};
                    if ($('#'+errorOutputBoxId).length) {
                        $('#'+errorOutputBoxId).hide();
                    }
                    if ($('#'+successOutputBoxId).length) {
                        $('#'+successOutputBoxId).hide();
                    }
                    restLoginForm(dataArray, function(getTokenData){
                        if (typeof getTokenData.error != 'undefined') {
                            if(typeof callbackRestLoginFormErrorHandling != "undefined" && typeof window[callbackRestLoginFormErrorHandling] == "function") {
                                window[callbackRestLoginFormErrorHandling](getTokenData, dataArray);
                            }
                            else if ($('#'+errorOutputBoxId).length) {
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
    window.console.log('Please set oauth_client_id, oauth_client_secret in parameters.yml for xrowrest.js.');
}

function restLoginForm(dataArray, callback){
    var request = {'grant_type': 'password',
                   'scope': 'openid'},
        form = dataArray.form,
        oauthSettings = dataArray.oauthSettings,
        jsoObj = dataArray.jsoObj;
    jsoObj.wipeTokens();
    localStorage.removeItem('xrowOIC');
    var errorIsSet = false,
        error_messages = {};
    $.each(form.serializeArray(), function(i, field) {
        if ((field.name == 'username' || field.name == 'password') && field.value == '')
            errorIsSet = true;
        if (field.name == 'error_messages[emptyfields]')
            error_messages['emptyfields'] = field.value;
        else if (field.name == 'error_messages[default]')
            error_messages['default'] = field.value;
        else
            request[field.name] = field.value;
    });
    if (errorIsSet === true && typeof error_messages['emptyfields'] != 'undefined' && error_messages['emptyfields'] != '')
        callback({'error': error_messages['emptyfields']});
    else {
        request.client_id = oauthSettings.client_id;
        request.client_secret = oauthSettings.client_secret;
        // 1. Request: get access_token
        $.ajax({
            type       : 'POST',
            xhrFields  : {
                withCredentials: true
            },
            crossDomain: true,
            url        : oauthSettings.baseURL+oauthSettings.tokenURL,
            data       : request
        }).done(function (requestData) {
            if (typeof requestData != "undefined") {
                if (typeof requestData.access_token != "undefined") {
                    // 2. Request: authenticate user
                    $.ajax({
                        type       : 'POST',
                        xhrFields  : {
                            withCredentials: true
                        },
                        crossDomain: true,
                        url        : oauthSettings.baseURL+oauthSettings.openIDConnectURL,
                        data       : {"access_token": requestData.access_token}
                    }).done(function (authRequest) {
                        if (typeof authRequest !== 'undefined' && typeof authRequest.result != 'undefined') {
                            $.ajax({
                                type : 'GET',
                                url  : oauthSettings.setcookieURL+'?access_token='+requestData.access_token+'&idsv='+authRequest.result.session_id+"",
                                cache: false
                            }).done(function (setCookieRequest) {
                                if (typeof setCookieRequest.error_description != "undefined") {
                                    var error = {'error': setCookieRequest.error_description};
                                    callback(error);
                                }
                                else {
                                    jsoObj.getToken(function(data) {
                                        callback(data);
                                    }, requestData);
                                }
                            });
                        }
                        else {
                            if (typeof error_messages['default'] != 'undefined' && error_messages['default'] != '')
                                var error = {'error': error_messages['default']};
                            else
                                var error = {'error': 'An unexpeded error occured xrjs0.'};
                            callback(error);
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
    }
};
function restLogout(oauthSettings, jsoObj, localStorageToken, redirectURL, sessionArray){
    if (typeof sessionArray != 'undefined') {
        $.ajax({
            type    : 'DELETE',
            xhrFields  : {
                withCredentials: true
            },
            crossDomain: true,
            url     : oauthSettings.baseURL+oauthSettings.apiLogoutURL+'/'+sessionArray.session_id
        }).done(function (logoutRequest) {
            document.cookie = sessionArray.session_name+'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            jsoObj.wipeTokens();
            if (redirectURL && redirectURL != '')
                window.location.href = redirectURL;
            else
                location.reload();
        });
    }
    else if(localStorageToken !== null && typeof localStorageToken.access_token != "undefined") {
        $.ajax({
            type    : 'GET',
            xhrFields  : {
                withCredentials: true
            },
            crossDomain: true,
            url     : oauthSettings.baseURL+oauthSettings.apiSessionURL+'?access_token='+localStorageToken.access_token
        }).done(function(sessionRequest, textStatus, jqXHR){
            if (typeof sessionRequest != 'undefined') {
                if (typeof sessionRequest.result != 'undefined')
                    restLogout(oauthSettings, jsoObj, null, redirectURL, sessionRequest.result);
                else if (sessionRequest.responseRetryReturn != 'undefined' && typeof sessionRequest.responseRetryReturn.result != 'undefined')
                    restLogout(oauthSettings, jsoObj, null, redirectURL, sessionRequest.responseRetryReturn.result);
            }
            else {
                var cookie = getCookie('eZSESSID');
                if (cookie != '')
                    restLogout(oauthSettings, jsoObj, null, redirectURL, {session_name: 'eZSESSID', session_id: cookie});
            }
        });
    }
    else {
        var cookie = getCookie('eZSESSID');
        if (cookie != '')
            restLogout(oauthSettings, jsoObj, null, redirectURL, {session_name: 'eZSESSID', session_id: cookie});
    }
};
function getCookie(name) {
    function escape(s) { return s.replace(/([.*+?\^${}()|\[\]\/\\])/g, '\\$1'); };
    var match = document.cookie.match(RegExp('(?:^|;\\s*)' + escape(name) + '=([^;]*)'));
    return match ? match[1] : null;
};