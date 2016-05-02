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
var xrowRestDoLogg = true;
if (typeof oauthSettings != "undefined" && typeof oauthSettings.client_id != "undefined" && typeof oauthSettings.baseURL != "undefined") {
    var localStorageToken = JSON.parse(localStorage.getItem(jwtProviderId));
    if (xrowRestDoLogg)
        console.log('localStorageToken', localStorageToken);
    if (localStorageToken === null) {
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
                var newToken = {'access_token': storageRequest.result.session_state,
                                'refresh_token': storageRequest.result.refresh_token,
                                'token_type': 'bearer',
                                'scope': 'user openid'};
                localStorage.setItem(jwtProviderId, JSON.stringify(newToken));
                localStorageToken = JSON.parse(localStorage.getItem(jwtProviderId));
                checkSessionIframe(localStorageToken);
                if (typeof snDomains != 'undefined' && snDomains.length > 0) {
                    var sesionCookieIsSet = JSON.parse(localStorage.getItem(lsKeyName));
                    if (!sesionCookieIsSet) {
                        localStorage.setItem(lsKeyName, JSON.stringify(snDomains));
                        if(typeof xrowRestfunctionsAfterUserIsLoggedIn != "undefined" && typeof xrowRestfunctionsAfterUserIsLoggedIn == "function") {
                            xrowRestfunctionsAfterUserIsLoggedIn(localStorageToken);
                        }
                    }
                }
            }
        });
    }
    else {
        if(typeof xrowRestfunctionsAfterUserIsLoggedIn != "undefined" && typeof xrowRestfunctionsAfterUserIsLoggedIn == "function") {
            xrowRestfunctionsAfterUserIsLoggedIn(localStorageToken);
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
                    var dataArray = {'form': $(this)},
                        errorOutputBoxId = dataArray.form.attr('id')+'-error',
                        successOutputBoxId = dataArray.form.attr('id')+'-success';
                    if ($('#'+errorOutputBoxId).length) {
                        $('#'+errorOutputBoxId).hide();
                    }
                    if ($('#'+successOutputBoxId).length) {
                        $('#'+successOutputBoxId).hide();
                    }
                    restLoginForm(dataArray);
                });
            });
        }
    });
} else {
    window.console.log('Please set oauth_client_id, oauth_client_secret in parameters.yml for xrowrest.js.');
}

function restLoginForm(dataArray){
    var request = {'grant_type': 'password',
                   'scope': 'openid'},
        form = dataArray.form;
    localStorage.removeItem(lsKeyName);
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
        loginResult({'error': error_messages['emptyfields']}, form);
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
                                    loginResult({'error': setCookieRequest.error_description}, form);
                                }
                                else {
                                    localStorage.setItem(jwtProviderId, JSON.stringify(requestData));
                                    loginResult(JSON.parse(localStorage.getItem(jwtProviderId)), form);
                                }
                            });
                        }
                        else {
                            if (typeof error_messages['default'] != 'undefined' && error_messages['default'] != '')
                                var error = {'error': error_messages['default']};
                            else
                                var error = {'error': 'An unexpeded error occured xrjs0.'};
                            loginResult(error, form);
                        }
                    });
                } else {
                    if (typeof requestData.error_description != "undefined")
                        var error = {'error': requestData.error_description};
                    else if(typeof requestData.responseJSON != "undefined" && typeof requestData.responseJSON.error_description != "undefined")
                        var error = {'error': requestData.responseJSON.error_description};
                    else
                        var error = {'error': 'An unexpeded error occured xrjs0.'};
                    loginResult(error, form);
                }
            }
        }).fail(function (jqXHR) {
            if(typeof jqXHR.responseJSON != "undefined" && typeof jqXHR.responseJSON.error_description != "undefined")
                var error = {'error': jqXHR.responseJSON.error_description};
            else
                var error = {'error': 'An unexpeded error occured: ' + jqXHR.statusText + ', HTTP Code ' + jqXHR.status + ':xrjs1.'};
            loginResult(error, form);
        });
    }
};
function loginResult(getTokenData, form) {
    var errorOutputBoxId = form.attr('id')+'-error',
        successOutputBoxId = form.attr('id')+'-success';
    if (typeof getTokenData.error != 'undefined') {
        if(typeof xrowRestLoginFormErrorHandling != "undefined" && typeof xrowRestLoginFormErrorHandling == "function") {
            xrowRestLoginFormErrorHandling(getTokenData, form);
        }
        else {
            if ($('#'+errorOutputBoxId).length) {
                $('#'+errorOutputBoxId).text(getTokenData.error).show();
            }
            else {
                window.console.log(getTokenData.error);
            }
        }
    }
    else if (typeof getTokenData.access_token != "undefined") {
        localStorage.setItem(jwtProviderId, JSON.stringify(getTokenData));
        localStorageToken = JSON.parse(localStorage.getItem(jwtProviderId));
    }
    if (localStorageToken !== null) {
        var redirectAfterApiLoginObject = form.find('input[name="redirectAfterApiLogin"]');
        if (redirectAfterApiLoginObject.length) {
            var redirectAfterApiLogin = redirectAfterApiLoginObject.val();
            // if value of redirect does not have /
            if (!redirectAfterApiLogin.match(/^http/) && !redirectAfterApiLogin.match(/^\//))
                redirectAfterApiLogin = '/'+redirectAfterApiLogin;
            window.location.href = redirectAfterApiLogin;
        }
        else {
            location.reload();
        }
    }
};
function restLogout(redirectURL, sessionArray){
    if (sessionArray !== null && typeof sessionArray.session_id != 'undefined') {
        $.ajax({
            type    : 'DELETE',
            xhrFields  : {
                withCredentials: true
            },
            crossDomain: true,
            url     : oauthSettings.baseURL+oauthSettings.apiLogoutURL+'/'+sessionArray.session_id
        }).done(function (logoutRequest) {
            localStorage.removeItem(jwtProviderId);
            if (redirectURL && redirectURL != '')
                window.location.href = redirectURL;
            else
                location.reload();
        });
    }
    else {
        var localStorageToken = JSON.parse(localStorage.getItem(jwtProviderId))
        if(localStorageToken !== null && typeof localStorageToken.access_token != "undefined") {
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
                        restLogout(redirectURL, sessionRequest.result);
                    else if (sessionRequest.responseRetryReturn != 'undefined' && typeof sessionRequest.responseRetryReturn.result != 'undefined')
                        restLogout(redirectURL, sessionRequest.responseRetryReturn.result);
                }
            });
        }
    }
};
function checkSessionIframe(localStorageToken) {
    if (localStorageToken !== null && typeof localStorageToken.access_token != "undefined") {
        // Add iframe with source to OP
        sessionIFrame = document.createElement("iframe");
        sessionIFrame.setAttribute("src", oauthSettings.baseURL+oauthSettings.checkSessionIframeURL);
        sessionIFrame.setAttribute("id", "receiveOPData");
        sessionIFrame.style.width = "0px";
        sessionIFrame.style.height = "0px";
        sessionIFrame.style.display = "none";
        document.body.appendChild(sessionIFrame);
        // Start checking the status on OP
        window.onload = function() {
            checkStatus(localStorageToken);
            // Check the status every [checkSessionDuration] seconds
            setInterval(function() {
                checkStatus(localStorageToken);
            }, 1000*checkSessionDuration);
        };

         // Get OP post message
         window.addEventListener('message', function(event){
             if (xrowRestDoLogg)
                 console.log('Client hat Sessionstatus vom OpenIDConnect-Provider erhalten: '+event.data);
             // Source is not the OP. Reject this request.
             if (event.origin !== oauthSettings.baseURL) {
                 if (xrowRestDoLogg)
                     console.log('Source is not the OpenIDConnect-Provider. Reject this request.');
                 return;
             }
             // User is logged out on API server.
             if (event.data != 'unchanged') {
                 if (xrowRestDoLogg)
                     console.log('User wurde auf dem API server ausgeloggt.');
                 $.ajax({
                     type    : 'GET',
                     url     : oauthSettings.apiSessionURL+'?access_token='+localStorageToken.access_token,
                     cache   : false
                 }).done(function(sessionRequest, textStatus, jqXHR){
                     if (typeof sessionRequest != 'undefined') {
                         if (typeof sessionRequest.result != 'undefined')
                             var sessionData = sessionRequest.result;
                         else if (sessionRequest.responseRetryReturn != 'undefined' && typeof sessionRequest.responseRetryReturn.result != 'undefined')
                             var sessionData = sessionRequest.responseRetryReturn.result;
                         // Destroy the session cookie on every domains
                         if (snDomains.length > 0) {
                             for (i = 0; i < snDomains.length; i++) {
                                 if (xrowRestDoLogg)
                                     console.log('Session für die domain '+snDomains[i]+' wird zerstört.');
                                 $.ajax({
                                     type    : 'DELETE',
                                     xhrFields  : {
                                         withCredentials: true
                                     },
                                     crossDomain: true,
                                     url     : snDomains[i]+oauthSettings.apiLogoutURL+'/'+sessionData.session_id
                                 }).done(function (logoutRequest) {
                                     if (snDomains.length == i) {
                                         // Destroy also localStoragItem for xrowOIC
                                         localStorage.removeItem(lsKeyName);
                                         restLogout('', sessionData);
                                     }
                                 });
                             }
                         }
                     }
                 });
             }
         });
    }
};
function checkStatus(localStorageToken) {
    var client = oauthSettings.client_id,
        text = client + ' ' + localStorageToken.access_token,
        receverWindow = document.getElementById('receiveOPData').contentWindow;
    receverWindow.postMessage(text, oauthSettings.baseURL);
    if (xrowRestDoLogg)
        console.log('Sessionstatus wird alle '+checkSessionDuration+' Sekunden geprüft.');
};