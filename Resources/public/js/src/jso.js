/**
 * JSO - Javascript OAuth Library
 *     Version 2.0
 *  UNINETT AS - http://uninett.no
 *  Author: Andreas Åkre Solberg <andreas.solberg@uninett.no>
 *  Licence: 
 *       
 *  Documentation available at: https://github.com/andreassolberg/jso
 */

define(function(require, exports, module) {

    var 
        default_config = {
            "lifetime": 3600,
            "debug": false,
        };

    var store = require('./store');
    var utils = require('./utils');
    var Config = require('./Config');


    var JSO = function(config) {

        this.config = new Config(default_config, config);
        this.providerID = this.getProviderID();

        JSO.instances[this.providerID] = this;

        this.callbacks = {};

        this.callbacks.redirect = JSO.redirect;
    };

    JSO.internalStates = [];
    JSO.instances = {};
    JSO.store = store;

    JSO.enablejQuery = function($) {
        JSO.$ = $;
    };


    JSO.redirect = function(url, callback) {
        window.location = url;
    };

    JSO.prototype.inappbrowser = function(params) {
        var that = this;
        return function(url, callback) {

            var onNewURLinspector = function(ref) {
                return function(inAppBrowserEvent) {

                    //  we'll check the URL for oauth fragments...
                    var url = inAppBrowserEvent.url;
                    if(this.config.get('debug') !== "false")
                        utils.log("loadstop event triggered, and the url is now " + url);

                    if (that.URLcontainsToken(url)) {
                        // ref.removeEventListener('loadstop', onNewURLinspector);
                        setTimeout(function() {
                            ref.close();
                        }, 500);

                        that.callback(url, function() {
                            // When we've found OAuth credentials, we close the inappbrowser...
                            if(this.config.get('debug') !== "false")
                                utils.log("Closing window ", ref);
                            if (typeof callback === 'function') callback();
                        });
                    }
                };
            };

            var target = '_blank';
            if (params.hasOwnProperty('target')) {
                target = params.target;
            }
            var options = {};

            if(this.config.get('debug') !== "false")
                utils.log("About to open url " + url);

            var ref = window.open(url, target, options);
            if(this.config.get('debug') !== "false")
                utils.log("URL Loaded... ");
            ref.addEventListener('loadstart', onNewURLinspector(ref));
            if(this.config.get('debug') !== "false")
                utils.log("Event listeren ardded... ", ref);
        };
    };

    JSO.prototype.on = function(eventid, callback) {
        if (typeof eventid !== 'string') throw new Error('Registering triggers on JSO must be identified with an event id');
        if (typeof callback !== 'function') throw new Error('Registering a callback on JSO must be a function.');

        this.callbacks[eventid] = callback;
    };


    /**
     * We need to get an identifier to represent this OAuth provider.
     * The JSO construction option providerID is preferred, if not provided
     * we construct a concatentaion of authorization url and client_id.
     * @return {[type]} [description]
     */
    JSO.prototype.getProviderID = function() {

        var c = this.config.get('providerID', null);
        if (c !== null) return c;

        var client_id = this.config.get('client_id', null, true);
        var authorization = this.config.get('authorization', null, true);

        return authorization + '|' + client_id;
    };




    /**
     * Do some sanity checking whether an URL contains a access_token in an hash fragment.
     * Used in URL change event trackers, to detect responses from the provider.
     * @param {[type]} url [description]
     */
    JSO.prototype.URLcontainsToken = function(url) {
        // If a url is provided 
        if (url) {
            if(url.indexOf('#') === -1) return false;
            h = url.substring(url.indexOf('#'));
        }

        /*
         * Start with checking if there is a token in the hash
         */
        if (h.length < 2) return false;
        if (h.indexOf("access_token") === -1) return false;
        return true;
    };

    /**
     * Check if the hash contains an access token. 
     * And if it do, extract the state, compare with
     * config, and store the access token for later use.
     *
     * The url parameter is optional. Used with phonegap and
     * childbrowser when the jso context is not receiving the response,
     * instead the response is received on a child browser.
     */
    JSO.prototype.callback = function(url, callback, providerID) {
        var 
            atoken,
            h = window.location.hash,
            now = utils.epoch(),
            state,
            instance;

        if(this.config.get('debug') !== "false")
            utils.log("JSO.prototype.callback() " + url + " callback=" + typeof callback);

        // If a url is provided 
        if (url) {
            if(url.indexOf('#') === -1) return;
            h = url.substring(url.indexOf('#'));
        }

        /*
         * Start with checking if there is a token in the hash
         */
        if (h.length < 2) return;
        if (h.indexOf("access_token") === -1) return;
        h = h.substring(1);
        atoken = utils.parseQueryString(h);

        if (atoken.state) {
            state = store.getState(atoken.state);
        } else {
            if (!providerID) {throw "Could not get [state] and no default providerid is provided.";}
            state = {providerID: providerID};
        }

        if (!state) throw "Could not retrieve state";
        if (!state.providerID) throw "Could not get providerid from state";
        if (!JSO.instances[state.providerID]) throw "Could not retrieve JSO.instances for this provider.";

        instance = JSO.instances[state.providerID];

        /**
         * If state was not provided, and default provider contains a scope parameter
         * we assume this is the one requested...
         */
        if (!atoken.state && co.scope) {
            state.scopes = instance._getRequestScopes();
            //if(this.config.get('debug') !== "false")
            //    utils.log("Setting state: ", state);
        }
        //if(this.config.get('debug') !== "false")
        //    utils.log("Checking atoken ", atoken, " and instance ", instance);

        /*
         * Decide when this token should expire.
         * Priority fallback:
         * 1. Access token expires_in
         * 2. Life time in config (may be false = permanent...)
         * 3. Specific permanent scope.
         * 4. Default library lifetime:
         */
        if (atoken.expires_in) {
            atoken.expires = now + parseInt(atoken.expires_in, 10);
        } else if (instance.config.get('default_lifetime', null) === false) {
            // Token is permanent.
        } else if (instance.config.has('permanent_scope')) {
            if (!store.hasScope(atoken, instance.config.get('permanent_scope'))) {
                atoken.expires = now + 3600*24*365*5;
            }
        } else if (instance.config.has('default_lifetime')) {
            atoken.expires = now + instance.config.get('default_lifetime');
        } else {
            atoken.expires = now + 3600;
        }

        /*
         * Handle scopes for this token
         */
        if (atoken.scope) {
            atoken.scopes = atoken.scope.split(" ");
        } else if (state.scopes) {
            atoken.scopes = state.scopes;
        }

        store.saveToken(state.providerID, atoken);

        if (state.restoreHash) {
            window.location.hash = state.restoreHash;
        } else {
            window.location.hash = '';
        }

        /*if(this.config.get('debug') !== "false") {
            utils.log(atoken);
            utils.log("Looking up internalStates storage for a stored callback... ", "state=" + atoken.state, JSO.internalStates);
        }*/

        if (JSO.internalStates[atoken.state] && typeof JSO.internalStates[atoken.state] === 'function') {
            //if(this.config.get('debug') !== "false")
            //    utils.log("InternalState is set, calling it now!");
            JSO.internalStates[atoken.state](atoken);
            delete JSO.internalStates[atoken.state];
        }

        //if(this.config.get('debug') !== "false")
        //    utils.log("Successfully obtain a token, now call the callback, and may be the window closes", callback);

        if (typeof callback === 'function') {
            callback(atoken);
        }
    };

    JSO.prototype.dump = function() {
        var txt = '';
        var tokens = store.getTokens(this.providerID);
        txt += 'Tokens: ' + "\n" + JSON.stringify(tokens, undefined, 4) + '\n\n';
        txt += 'Config: ' + "\n" + JSON.stringify(this.config, undefined, 4) + "\n\n";
        return txt;
    };

    JSO.prototype._getRequestScopes = function(opts) {
        var scopes = [], i;
        /*
         * Calculate which scopes to request, based upon provider config and request config.
         */
        if (this.config.get('scopes') && this.config.get('scopes').request) {
            for(i = 0; i < this.config.get('scopes').request.length; i++) scopes.push(this.config.get('scopes').request[i]);
        }
        if (opts && opts.scopes && opts.scopes.request) {
            for(i = 0; i < opts.scopes.request.length; i++) scopes.push(opts.scopes.request[i]);
        }
        return utils.uniqueList(scopes);
    };

    JSO.prototype._getRequiredScopes = function(opts) {
        var scopes = [], i;
        /*
         * Calculate which scopes to request, based upon provider config and request config.
         */
        if (this.config.get('scopes') && this.config.get('scopes').require) {
            for(i = 0; i < this.config.get('scopes').require.length; i++) scopes.push(this.config.get('scopes').require[i]);
        }
        if (opts && opts.scopes && opts.scopes.require) {
            for(i = 0; i < opts.scopes.require.length; i++) scopes.push(opts.scopes.require[i]);
        }
        return utils.uniqueList(scopes);
    };

    JSO.prototype.getToken = function(callback, opts) {
        var scopesRequire = this._getRequiredScopes(opts);
        var token = store.getToken(this.providerID, scopesRequire);

        if (token) {
            return callback(token);
        } else {
            this._authorize(callback, opts);
        }

    };

    JSO.prototype.checkToken = function(opts) {
        var scopesRequire = this._getRequiredScopes(opts);
        return store.getToken(this.providerID, scopesRequire);
    };

    JSO.prototype._authorize = function(callback, opts) {
        var 
            request,
            authurl,
            scopes;

        var authorization = this.config.get('authorization', null, true);
        var client_id = this.config.get('client_id', null, true);

        /*if(this.config.get('debug') !== "false") {
            utils.log("About to send an authorization request to this entry:", authorization);
            utils.log("Options", opts, "callback", callback);
        }*/

        request = {
            "response_type": "token",
            "state": utils.uuid()
        };

        if (callback && typeof callback === 'function') {
            //if(this.config.get('debug') !== "false")
            //    utils.log("About to store a callback for later with state=" + request.state, callback);
            JSO.internalStates[request.state] = callback;
        }

        if (this.config.has('redirect_uri')) {
            request.redirect_uri = this.config.get('redirect_uri', '');
        }

        request.client_id = client_id;

        /*
         * Calculate which scopes to request, based upon provider config and request config.
         */
        scopes = this._getRequestScopes(opts);
        if (scopes.length > 0) {
            request.scope = utils.scopeList(scopes);
        }

        if(opts.access_token !== "undefined")
            request.access_token = opts.access_token;
        else
            return false;
        //if(this.config.get('debug') !== "false")
        //    utils.log("DEBUG REQUEST"); utils.log(request);

        authurl = utils.encodeURL(authorization, request);

        // We'd like to cache the hash for not loosing Application state. 
        // With the implciit grant flow, the hash will be replaced with the access
        // token when we return after authorization.
        if (window.location.hash) {
            request.restoreHash = window.location.hash;
        }
        request.providerID = this.providerID;
        if (scopes) {
            request.scopes = scopes;
        }

        /*if(this.config.get('debug') !== "false") {
            utils.log("Saving state [" + request.state + "]");
            utils.log(JSON.parse(JSON.stringify(request)));
        }*/

        store.saveState(request.state, request);
        $.ajax({
            type    : 'get',
            url     : authurl
        }).done(function (data) {
            if(typeof data.result != "undefined")
                callback( authurl );
            else {
                if(typeof data.responseJSON != "undefined") {
                    if (typeof data.responseJSON.error_description != "undefined") 
                        alert(data.responseJSON.error_description);
                }
                else
                    window.console.log("An unexpeded error occured xrjs00.");
            }
        }).fail(function (jqXHR) {
            window.console.log("An unexpeded error occured: " + jqXHR.statusText + ", HTTP Code " + jqXHR.status + ":xrjs3.");
        });
        //this.gotoAuthorizeURL(authurl, callback);
    };


    JSO.prototype.gotoAuthorizeURL = function(url, callback) {

        if (!this.callbacks.redirect || typeof this.callbacks.redirect !== 'function') 
            throw new Error('Cannot redirect to authorization endpoint because of missing redirect handler');

        this.callbacks.redirect(url, callback);

    };

    JSO.prototype.wipeTokens = function() {
        store.wipeTokens(this.providerID);
    };


    JSO.prototype.ajax = function(settings) {

        var 
            allowia,
            scopes,
            token,
            providerid,
            co;

        var that = this;

        if (!JSO.hasOwnProperty('$')) throw new Error("JQuery support not enabled.");

        oauthOptions = settings.oauth || {};

        var errorOverridden = settings.error || null;
        settings.error = function(jqXHR, textStatus, errorThrown) {
            /*if(this.config.get('debug') !== "false") {
                utils.log('error(jqXHR, textStatus, errorThrown)');
                utils.log(jqXHR);
                utils.log(textStatus);
                utils.log(errorThrown);
            }*/

            if (jqXHR.status === 401) {
                /*if(this.config.get('debug') !== "false") {
                    utils.log("Token expired. About to delete this token");
                    utils.log(token);
                }*/
                that.wipeTokens();

            }
            if (errorOverridden && typeof errorOverridden === 'function') {
                errorOverridden(jqXHR, textStatus, errorThrown);
            }
        };


        return this.getToken(function(token) {
            //if(this.config.get('debug') !== "false")
            //    utils.log("Ready. Got an token, and ready to perform an AJAX call", token);

            if (that.config.get('presenttoken', null) === 'qs') {
                // settings.url += ((h.indexOf("?") === -1) ? '?' : '&') + "access_token=" + encodeURIComponent(token["access_token"]);
                if (!settings.data) settings.data = {};
                settings.data.access_token = token.access_token;
            } else {
                if (!settings.headers) settings.headers = {};
                settings.headers.Authorization = "Bearer " + token.access_token;
            }
            //if(this.config.get('debug') !== "false")
            //    utils.log('$.ajax settings', settings);
            return JSO.$.ajax(settings);

        }, oauthOptions);
        
    };
    return JSO;
});