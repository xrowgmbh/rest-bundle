System.register(["angular2/core", "angular2/http", "angular2/common", 'rxjs/Rx', "http.service", "jwt.service", "cast.response.to.object"], function(exports_1, context_1) {
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
    var core_1, http_1, common_1, http_service_1, jwt_service_1, cast_response_to_object_1;
    var AppComponent;
    return {
        setters:[
            function (core_1_1) {
                core_1 = core_1_1;
            },
            function (http_1_1) {
                http_1 = http_1_1;
            },
            function (common_1_1) {
                common_1 = common_1_1;
            },
            function (_1) {},
            function (http_service_1_1) {
                http_service_1 = http_service_1_1;
            },
            function (jwt_service_1_1) {
                jwt_service_1 = jwt_service_1_1;
            },
            function (cast_response_to_object_1_1) {
                cast_response_to_object_1 = cast_response_to_object_1_1;
            }],
        execute: function() {
            AppComponent = (function () {
                function AppComponent(_httpService, _jwtService) {
                    this._httpService = _httpService;
                    this._jwtService = _jwtService;
                    this.showUserData = false;
                    this.showErrorText = false;
                    this.loginDataEmpty = 'Bitte geben Sie Ihren Benutzernamen und Ihr Passwort ein.';
                }
                AppComponent.prototype.ngOnInit = function () {
                    var _this = this;
                    console.log("Application component initialized ...");
                    // Get the JWT
                    var jwToken = this._jwtService.get();
                    if (jwToken !== null) {
                        this.showUserData = true;
                        this._httpService.getUserData(jwToken.access_token)
                            .subscribe(function (response) {
                            if (typeof _this._httpService.responseUser != "undefined") {
                                var castedUser = new cast_response_to_object_1.CastResponseToOobject(_this._httpService.responseUser);
                                if (castedUser.result === "undefined") {
                                    _this.handleErrors(castedUser);
                                }
                                else {
                                    _this.userData = castedUser.result;
                                }
                            }
                            if (typeof _this._httpService.responseAccount != "undefined") {
                                var castedAccount = new cast_response_to_object_1.CastResponseToOobject(_this._httpService.responseAccount);
                                if (castedAccount.result === "undefined") {
                                    _this.handleErrors(castedAccount);
                                }
                                else {
                                    _this.accountData = castedAccount.result;
                                }
                            }
                        }, function (errorResponse) {
                            _this.handleErrors(errorResponse);
                        });
                    }
                };
                AppComponent.prototype.login = function (event, username, password) {
                    var _this = this;
                    event.preventDefault();
                    this.showErrorText = false;
                    this.errorText = '';
                    if (username == '' || password == '') {
                        this.errorText = this.loginDataEmpty;
                        this.showErrorText = true;
                    }
                    else {
                        var loginRequestData = "grant_type=password&client_id=" + this._httpService._oauthSettings.client_id + "&client_secret=" + this._httpService._oauthSettings.client_secret + "&username=" + username + "&password=" + password;
                        this._httpService._http
                            .post(this._httpService._oauthSettings.baseURL + this._httpService._oauthSettings.tokenURL, loginRequestData, this._httpService._headerOptions)
                            .subscribe(function (loginResponse) {
                            _this._httpService.authenticate(loginResponse.json());
                        }, function (errorLoginResponse) {
                            var castedLoginResponse = new cast_response_to_object_1.CastResponseToOobject(errorLoginResponse);
                            _this.handleErrors(castedLoginResponse);
                        });
                    }
                };
                AppComponent.prototype.logout = function (event) {
                    event.preventDefault();
                    this._httpService.logout();
                };
                AppComponent.prototype.handleErrors = function (errorResponse) {
                    var errorJson = errorResponse.json();
                    var errorStatus = errorResponse.status;
                    var error = new cast_response_to_object_1.CastResponseToOobject(errorJson);
                    // Token is expired
                    if (errorStatus == 401) {
                        return this._httpService.refreshToken();
                    }
                    else {
                        if (error.error_description != 'undefined') {
                            this.errorText = error.error_description;
                            this.showErrorText = true;
                        }
                        else if (error.error != 'undefined') {
                            this.errorText = error.error;
                            this.showErrorText = true;
                        }
                    }
                };
                AppComponent = __decorate([
                    core_1.Injectable(),
                    core_1.Component({
                        selector: "app",
                        templateUrl: "/bundles/xrowrest/js/angular/openIDConnect/build/app.html",
                        directives: [common_1.CORE_DIRECTIVES, common_1.FORM_DIRECTIVES],
                        providers: [
                            http_1.HTTP_PROVIDERS,
                            http_service_1.HttpService,
                            jwt_service_1.JwtService
                        ]
                    }), 
                    __metadata('design:paramtypes', [http_service_1.HttpService, jwt_service_1.JwtService])
                ], AppComponent);
                return AppComponent;
            }());
            exports_1("AppComponent", AppComponent);
        }
    }
});

//# sourceMappingURL=app.component.js.map
