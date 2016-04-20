System.register(["angular2/core", "angular2/http", "rxjs/Observable", 'rxjs/Rx', "jwt.service", "cast.response.to.object"], function(exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
        var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
        if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
        else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
    var __metadata = (this && this.__metadata) || function (k, v) {
        if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
    };
    var core_1, http_1, Observable_1, jwt_service_1, cast_response_to_object_1;
    var HttpService;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (http_1_1) {
                http_1 = http_1_1;
            },
            function (Observable_1_1) {
                Observable_1 = Observable_1_1;
            },
            function (_1) {},
            function (jwt_service_1_1) {
                jwt_service_1 = jwt_service_1_1;
            },
            function (cast_response_to_object_1_1) {
                cast_response_to_object_1 = cast_response_to_object_1_1;
            }],
        execute: function() {
            HttpService = (function () {
                function HttpService(_http, _jwtService) {
                    this._http = _http;
                    this._jwtService = _jwtService;
                    this._oauthSettings = oauthSettings;
                    this._userUrl = this._oauthSettings.endpointPrefix + '/user';
                    this._accountUrl = this._oauthSettings.endpointPrefix + '/account';
                    this._subscriptionsUrl = this._oauthSettings.endpointPrefix + '/subscriptions';
                    this._headerOptions = new http_1.RequestOptions({ headers: new http_1.Headers({ 'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json' }) });
                }
                /*
                 * Authenticate
                 */
                HttpService.prototype.authenticate = function (loginResponseData) {
                    var _this = this;
                    var authRequestData = "access_token=" + loginResponseData.access_token;
                    this._http
                        .post(this._oauthSettings.baseURL + this._oauthSettings.openIDConnectURL, authRequestData, this._headerOptions)
                        .subscribe(function (authResponse) {
                        _this.setDomainCookie(authResponse.json(), loginResponseData);
                    }, function (errorAuthResponse) { return _this.handleError(errorAuthResponse, 'AUTH FEHLER: '); });
                };
                /*
                 * Set locale cookie for user domain
                 */
                HttpService.prototype.setDomainCookie = function (authResponseData, loginResponseData) {
                    var _this = this;
                    var castedAuthResponse = new cast_response_to_object_1.CastResponseToOobject(authResponseData);
                    this._http
                        .get(this._oauthSettings.setcookieURL + "?access_token=" + loginResponseData.access_token + "&idsv=" + castedAuthResponse.result.session_id + "")
                        .subscribe(function (setCookieResponse) {
                        _this._jwtService.set(loginResponseData);
                        window.location.reload();
                    }, function (errorSetCookieResponse) { return _this.handleError(errorSetCookieResponse, 'SETCOOKIE FEHLER: '); });
                };
                /*
                 * Get user and account data from CRM
                 */
                HttpService.prototype.getUserData = function (accessToken) {
                    var _this = this;
                    var accessTokenRequestData = "?access_token=" + accessToken;
                    return Observable_1.Observable.forkJoin(this._http
                        .get(this._oauthSettings.baseURL + this._userUrl + accessTokenRequestData, this._headerOptions)
                        .map(function (res) { return _this.responseUser = res.json(); }), this._http
                        .get(this._oauthSettings.baseURL + this._accountUrl + accessTokenRequestData, this._headerOptions)
                        .map(function (res) { return _this.responseAccount = res.json(); }));
                };
                /*
                 * Refresh the access_token with the refresh_token
                 */
                HttpService.prototype.refreshToken = function () {
                    var _this = this;
                    var jwToken = this._jwtService.get();
                    if (jwToken !== null && typeof jwToken.refresh_token != "undefined") {
                        var refreshTokenRequestData = "grant_type=refresh_token&client_id=" + this._oauthSettings.client_id + "&client_secret=" + this._oauthSettings.client_secret + "&refresh_token=" + jwToken.refresh_token;
                        this._http
                            .post(this._oauthSettings.baseURL + this._oauthSettings.tokenURL, refreshTokenRequestData, this._headerOptions)
                            .subscribe(function (refreshTokenResponse) {
                            _this._jwtService.set(refreshTokenResponse.json());
                            window.location.reload();
                        }, function (errorRefreshTokenResponse) { return _this.handleError(errorRefreshTokenResponse, 'refreshToken FEHLER: '); });
                    }
                    else {
                        console.log('refreshToken FEHLER: jwToken oder jwToken.refresh_token ist nicht gesetzt.', jwToken);
                    }
                };
                /*
                 * Logout the user
                 */
                HttpService.prototype.logout = function () {
                    var _this = this;
                    var jwToken = this._jwtService.get();
                    if (jwToken !== null && typeof jwToken.access_token != "undefined") {
                        this._http
                            .get(this._oauthSettings.baseURL + this._oauthSettings.apiSessionURL + "?access_token=" + jwToken.access_token, this._headerOptions)
                            .subscribe(function (sessionResponse) {
                            if (sessionResponse.status == 200) {
                                var castedSessionResponse = new cast_response_to_object_1.CastResponseToOobject(sessionResponse.json());
                                //console.log('castedSessionResponse', castedSessionResponse);
                                //return true;
                                _this._http
                                    .delete(_this._oauthSettings.baseURL + _this._oauthSettings.apiLogoutURL + '/' + castedSessionResponse.session_id, _this._headerOptions)
                                    .subscribe(function (logoutResponse) {
                                    _this._jwtService.remove();
                                    window.location.reload();
                                }, function (errorLogoutResponse) { return _this.handleError(errorLogoutResponse, 'Logout FEHLER: '); });
                            }
                        }, function (errorSessionResponse) { return _this.handleError(errorSessionResponse, 'Logout Session FEHLER: '); });
                    }
                    else {
                        console.log('LOGOUT FEHLER: jwToken oder jwToken.access_token ist nicht gesetzt.', jwToken);
                    }
                };
                HttpService.prototype.handleError = function (error, message) {
                    //console.error(message, error.json());
                    throw Error(error.json());
                };
                HttpService = __decorate([
                    core_1.Injectable(), 
                    __metadata('design:paramtypes', [http_1.Http, jwt_service_1.JwtService])
                ], HttpService);
                return HttpService;
            }());
            exports_1("HttpService", HttpService);
        }
    }
});

//# sourceMappingURL=http.service.js.map
