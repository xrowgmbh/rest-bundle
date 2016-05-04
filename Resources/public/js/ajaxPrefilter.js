jQuery.ajaxPrefilter(function(opts, originalOpts, jqXHR) {
    // you could pass this option in on a "retry" so that it doesn't
    // get all recursive on you.
    if (opts.retryAttempt) {
        return;
    }
    var dfd = jQuery.Deferred(),
        doAjaxPrefilterLogg = false;

    // if the request works, return normally
    jqXHR.done(dfd.resolve);
    jqXHR.fail(function() {
        var args = Array.prototype.slice.call(arguments);
        if (doAjaxPrefilterLogg)
            window.console.log('jqXHR FAIL', jqXHR);
        var defaultErrorText = 'Es ist ein Fehler beim Kommunizieren mit dem Server aufgetreten. Bitte versuchen Sie es später erneut.',
            errorText = '';
        if (jqXHR.status != 401) {
            if ((jqXHR.responseText + '').indexOf('Invalid refresh token', 0) !== -1) {
                var localStorageToken = JSON.parse(localStorage.getItem(jwtProviderId));
                if (localStorageToken !== null && typeof localStorageToken.access_token != 'undefined')
                    restLogout('', null);
                else
                    restLogout('', null);
                dfd.resolveWith(this, [{"statusText": "Access token NOT refreshed. Logout the user."}]);
            }
            else {
                if (typeof jqXHR.responseJSON != 'undefined') {
                    errorText = jqXHR.responseJSON.error_description;
                }
                else if (typeof jqXHR.statusText != 'undefined') {
                    errorText = jqXHR.statusText;
                }
                if (errorText == 'error') {
                    errorText = defaultErrorText;
                }
                dfd.resolveWith(this, [{"error_description": errorText}]);
            }
        }
        else {
            if ((jqXHR.responseText + '').indexOf('TOKENEXPIREDERROR', 0) !== -1) {
                var originObj = this;
                var localStorageToken = JSON.parse(localStorage.getItem(jwtProviderId));
                if (localStorageToken !== null && typeof localStorageToken.access_token != 'undefined') {
                    // Refresh access token
                    var refreshParams = {'client_id': oauthSettings.client_id,
                                         'client_secret': oauthSettings.client_secret,
                                         'refresh_token': localStorageToken.refresh_token,
                                         'grant_type': 'refresh_token'};
                    jQuery.ajax({
                        type       : 'POST',
                        xhrFields  : {
                            withCredentials: true
                        },
                        crossDomain: true,
                        url        : oauthSettings.baseURL+oauthSettings.tokenURL,
                        data       : refreshParams
                    }).done(function (responseRequest) {
                        if (typeof responseRequest != "undefined" && responseRequest.access_token != "undefined") {
                            localStorage.setItem(jwtProviderId, JSON.stringify(responseRequest));
                            originObj.url = parseAndRenewURL(originObj.url, {'access_token': responseRequest.access_token});
                            var options = {
                                type       : originObj.type,
                                url        : originObj.url
                            };
                            if (typeof originObj.xhrFields != 'undefined') {
                                options['xhrFields'] = originObj.xhrFields;
                            }
                            if (typeof originObj.crossDomain != 'undefined') {
                                options['crossDomain'] = originObj.crossDomain;
                            }
                            if (doAjaxPrefilterLogg)
                                window.console.log('retryOriginFunction options', options);
                            jQuery.ajax(options).done(function (responseReturn) {
                                if (doAjaxPrefilterLogg)
                                    window.console.log('retryOriginFunction responseRequest', responseReturn);
                                if (responseReturn !== null) {
                                    dfd.resolveWith(this, [{"responseRetryReturn": responseReturn,
                                                            "statusText": "Access token refreshed",
                                                            "access_token": responseRequest.access_token}]);
                                }
                            }).fail(function () {
                                dfd.resolveWith(this, [{"statusText": "Access token refreshed. But retryOriginFunction FAILS."}]);
                            });
                        }
                        else if (typeof restLogout == 'function') {
                            // Logout
                            restLogout('', null);
                            dfd.resolveWith(this, [{"statusText": "Access token NOT refreshed. Log out the user."}]);
                        }
                    });
                }
                else if (typeof restLogout == 'function') {
                    // Logout
                    restLogout('', null);
                    dfd.resolveWith(this, [{"statusText": "Log out the user."}]);
                }
            }
            else if (typeof restLogout == 'function') {
                // Logout
                restLogout('', null);
                dfd.resolveWith(this, [{"statusText": "Log out the user."}]);
            }
        }
    });

    // NOW override the jqXHR's promise functions with our deferred
    return dfd.promise(jqXHR);
    //return jqXHR;
});

function parseAndRenewURL(url, queryReplaces) {
    var parser = document.createElement('a'),
        searchObject = {},
        queries, split, i, newQuery = '';
    // Let the browser do the work
    parser.href = url;
    // Convert query string to object
    queries = parser.search.replace(/^\?/, '').split('&');
    for( i = 0; i < queries.length; i++ ) {
        split = queries[i].split('=');
        var name = split[0],
            value = split[1];
        if (typeof queryReplaces != 'undefined') {
            if (typeof queryReplaces[name] != 'undefined')
                value = queryReplaces[name];
        }
        if (newQuery != '')
            newQuery += '&';
        newQuery += name+'='+value;
        //searchObject[name] = value;
    }
    if (newQuery != '')
        newQuery = '?'+newQuery;
    var newURL = parser.protocol+'//'+parser.hostname+parser.pathname+newQuery;
    /*var parseURLArray =  {
                protocol: parser.protocol,
                host: parser.host,
                hostname: parser.hostname,
                port: parser.port,
                pathname: parser.pathname,
                search: parser.search,
                query: newQuery,
                searchObject: searchObject,
                hash: parser.hash
    };*/
    return newURL;
};