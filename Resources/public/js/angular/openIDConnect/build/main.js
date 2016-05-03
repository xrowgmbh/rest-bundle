var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
System.register("api.gateway.service", ["angular2/core", "angular2/http", "rxjs/Observable", "rxjs/Subject", "rxjs/Rx"], function(exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var core_1, http_1, Observable_1, Subject_1;
    var ApiGatewayOptions, ApiGateway;
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
            function (Subject_1_1) {
                Subject_1 = Subject_1_1;
            },
            function (_1) {}],
        execute: function() {
            ApiGatewayOptions = (function () {
                function ApiGatewayOptions() {
                    this.headers = new http_1.Headers({ 'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json' });
                }
                return ApiGatewayOptions;
            }());
            exports_1("ApiGatewayOptions", ApiGatewayOptions);
            ApiGateway = (function () {
                function ApiGateway(http) {
                    this.http = http;
                    // Define the internal Subject we'll use to push errors
                    this.errorsSubject = new Subject_1.Subject();
                    // Define the internal Subject we'll use to push the command count
                    this.pendingCommandsSubject = new Subject_1.Subject();
                    this.pendingCommandCount = 0;
                    // Create our observables from the subjects
                    this.errors$ = this.errorsSubject.asObservable();
                    this.pendingCommands$ = this.pendingCommandsSubject.asObservable();
                }
                ApiGateway.prototype.get = function (url) {
                    var options = new ApiGatewayOptions();
                    options.method = http_1.RequestMethod.Get;
                    options.url = url;
                    return this.request(options);
                };
                ApiGateway.prototype.post = function (url, data) {
                    var options = new ApiGatewayOptions();
                    options.method = http_1.RequestMethod.Post;
                    options.url = url;
                    options.data = data;
                    return this.request(options);
                };
                ApiGateway.prototype.request = function (options) {
                    var _this = this;
                    options.method = (options.method || http_1.RequestMethod.Get);
                    var requestOptions = new http_1.RequestOptions();
                    requestOptions.method = options.method;
                    requestOptions.url = options.url;
                    requestOptions.headers = options.headers;
                    requestOptions.body = options.data;
                    var isCommand = (options.method !== http_1.RequestMethod.Get);
                    if (isCommand) {
                        this.pendingCommandsSubject.next(++this.pendingCommandCount);
                    }
                    var stream = this.http.request(options.url, requestOptions)
                        .catch(function (error) {
                        _this.errorsSubject.next(error);
                        return Observable_1.Observable.throw(error);
                    })
                        .map(this.unwrapHttpValue)
                        .catch(function (error) {
                        return Observable_1.Observable.throw(_this.unwrapHttpError(error));
                    })
                        .finally(function () {
                        if (isCommand) {
                            _this.pendingCommandsSubject.next(--_this.pendingCommandCount);
                        }
                    });
                    return stream;
                };
                ApiGateway.prototype.extractValue = function (collection, key) {
                    var value = collection[key];
                    delete (collection[key]);
                    return value;
                };
                ApiGateway.prototype.unwrapHttpError = function (error) {
                    try {
                        return (error.json());
                    }
                    catch (jsonError) {
                        return ({
                            code: -1,
                            message: "An unexpected error occurred."
                        });
                    }
                };
                ApiGateway.prototype.unwrapHttpValue = function (value) {
                    return (value.json());
                };
                ApiGateway = __decorate([
                    core_1.Injectable(), 
                    __metadata('design:paramtypes', [http_1.Http])
                ], ApiGateway);
                return ApiGateway;
            }());
            exports_1("ApiGateway", ApiGateway);
        }
    }
});
System.register("api.service", ["angular2/core", "rxjs/Rx", "api.gateway.service"], function(exports_2, context_2) {
    "use strict";
    var __moduleName = context_2 && context_2.id;
    var core_2, api_gateway_service_1;
    var ApiService;
    return {
        setters:[
            function (core_2_1) {
                core_2 = core_2_1;
            },
            function (_2) {},
            function (api_gateway_service_1_1) {
                api_gateway_service_1 = api_gateway_service_1_1;
            }],
        execute: function() {
            ApiService = (function () {
                function ApiService(_apiGateway) {
                    this._apiGateway = _apiGateway;
                    this._apiSettings = oauthSettings;
                }
                /*
                 * Login
                 */
                ApiService.prototype.login = function (username, password) {
                    var loginRequestData = "grant_type=password&client_id=" + this._apiSettings.client_id + "&client_secret=" + this._apiSettings.client_secret + "&username=" + encodeURIComponent(username) + "&password=" + password;
                    return this._apiGateway.post(this._apiSettings.baseURL + this._apiSettings.tokenURL, loginRequestData);
                };
                /*
                 * Authenticate
                 */
                ApiService.prototype.authenticate = function (loginResponseData) {
                    var authRequestData = "access_token=" + loginResponseData.access_token;
                    return this._apiGateway.post(this._apiSettings.baseURL + this._apiSettings.openIDConnectURL, authRequestData);
                };
                /*
                 * Copy session cookie for user domain
                 */
                ApiService.prototype.setDomainCookie = function (loginResponseData, authResponseData) {
                    return this._apiGateway.get(this._apiSettings.setcookieURL + "?access_token=" + loginResponseData.access_token + "&idsv=" + authResponseData.result.session_id + "");
                };
                /*
                 * Check if user is logged in
                 */
                ApiService.prototype.checkSession = function () {
                    return this._apiGateway.get(this._apiSettings.baseURL + '/xrowapi/v2/storage');
                };
                ApiService = __decorate([
                    core_2.Injectable(), 
                    __metadata('design:paramtypes', [api_gateway_service_1.ApiGateway])
                ], ApiService);
                return ApiService;
            }());
            exports_2("ApiService", ApiService);
        }
    }
});
System.register("jwt.service", ["angular2/core"], function(exports_3, context_3) {
    "use strict";
    var __moduleName = context_3 && context_3.id;
    var core_3;
    var JwtService;
    return {
        setters:[
            function (core_3_1) {
                core_3 = core_3_1;
            }],
        execute: function() {
            JwtService = (function () {
                function JwtService() {
                }
                JwtService.prototype.get = function (name) {
                    var token = JSON.parse(localStorage.getItem(name));
                    return token;
                };
                JwtService.prototype.set = function (name, token) {
                    // If token is refreshed
                    if (typeof token.refresh_token == 'undefined') {
                        var oldJwToken = this.get(name);
                        if (oldJwToken !== null) {
                            if (typeof oldJwToken.refresh_token != 'undefined') {
                                token.refresh_token = oldJwToken.refresh_token;
                            }
                        }
                    }
                    var jwToken = JSON.stringify(token);
                    localStorage.setItem(name, jwToken);
                    return true;
                };
                JwtService.prototype.remove = function (name) {
                    localStorage.removeItem(name);
                    return true;
                };
                JwtService = __decorate([
                    core_3.Injectable(), 
                    __metadata('design:paramtypes', [])
                ], JwtService);
                return JwtService;
            }());
            exports_3("JwtService", JwtService);
        }
    }
});
System.register("error.handler", ["angular2/core", "api.gateway.service", "api.service"], function(exports_4, context_4) {
    "use strict";
    var __moduleName = context_4 && context_4.id;
    var core_4, api_gateway_service_2, api_service_1;
    var ErrorHandler;
    return {
        setters:[
            function (core_4_1) {
                core_4 = core_4_1;
            },
            function (api_gateway_service_2_1) {
                api_gateway_service_2 = api_gateway_service_2_1;
            },
            function (api_service_1_1) {
                api_service_1 = api_service_1_1;
            }],
        execute: function() {
            ErrorHandler = (function () {
                function ErrorHandler(_apiGateway, _apiService) {
                    this._apiGateway = _apiGateway;
                    _apiGateway.errors$.subscribe(function (value) {
                        console.group("HttpErrorHandler");
                        console.log(value.status, "status code detected.");
                        console.dir(value);
                        console.groupEnd();
                    });
                }
                ErrorHandler = __decorate([
                    core_4.Injectable(), 
                    __metadata('design:paramtypes', [api_gateway_service_2.ApiGateway, api_service_1.ApiService])
                ], ErrorHandler);
                return ErrorHandler;
            }());
            exports_4("ErrorHandler", ErrorHandler);
        }
    }
});
System.register("app.component", ["angular2/core", "angular2/common", "rxjs/Rx", "api.service", "jwt.service", "error.handler"], function(exports_5, context_5) {
    "use strict";
    var __moduleName = context_5 && context_5.id;
    var core_5, common_1, api_service_2, jwt_service_1, error_handler_1;
    var AppComponent;
    return {
        setters:[
            function (core_5_1) {
                core_5 = core_5_1;
            },
            function (common_1_1) {
                common_1 = common_1_1;
            },
            function (_3) {},
            function (api_service_2_1) {
                api_service_2 = api_service_2_1;
            },
            function (jwt_service_1_1) {
                jwt_service_1 = jwt_service_1_1;
            },
            function (error_handler_1_1) {
                error_handler_1 = error_handler_1_1;
            }],
        execute: function() {
            AppComponent = (function () {
                function AppComponent(_apiService, _jwtService, _elRef, injector) {
                    this._apiService = _apiService;
                    this._jwtService = _jwtService;
                    this._elRef = _elRef;
                    this.showErrorText = false;
                    this.userIsLoggedIn = false;
                    this.errorText = 'Please fill in required data.';
                    this.buttonText = 'Login';
                    this.buttonActiveText = 'Login';
                    this.buttonWaitingText = 'Please wait...';
                    this.redirectUrl = '';
                    this.doLogg = false;
                    this.jwtProviderId = 'xrow-rest-token';
                    this.lsKeyName = 'xrowOpenIDConnect';
                    injector.get(error_handler_1.ErrorHandler);
                    // Enable button
                    this.setButton('enabled');
                    // Set login empty fields error if exists
                    if (_elRef.nativeElement.getAttribute('errorLoginEmptyfields') != '')
                        this.errorText = _elRef.nativeElement.getAttribute('errorLoginEmptyfields');
                    // Set redirect url if exists
                    if (_elRef.nativeElement.getAttribute('redirectAfterApiLogin') !== null && _elRef.nativeElement.getAttribute('redirectAfterApiLogin') != '') {
                        this.redirectUrl = _elRef.nativeElement.getAttribute('redirectAfterApiLogin');
                        if (!this.redirectUrl.match(/^http/) && !this.redirectUrl.match(/^\//)) {
                            this.redirectUrl = '/' + this.redirectUrl;
                        }
                    }
                    if (_elRef.nativeElement.getAttribute('buttonActiveText') !== null && _elRef.nativeElement.getAttribute('buttonActiveText') != '')
                        this.buttonActiveText = _elRef.nativeElement.getAttribute('buttonActiveText');
                    if (_elRef.nativeElement.getAttribute('buttonWaitingText') !== null && _elRef.nativeElement.getAttribute('buttonWaitingText') != '')
                        this.buttonWaitingText = _elRef.nativeElement.getAttribute('buttonWaitingText');
                    if (_elRef.nativeElement.getAttribute('jwtProviderId') !== null && _elRef.nativeElement.getAttribute('jwtProviderId') != '')
                        this.jwtProviderId = _elRef.nativeElement.getAttribute('jwtProviderId');
                }
                AppComponent.prototype.ngOnInit = function () {
                    // Get the JWT
                    var jwToken = this._jwtService.get(this.jwtProviderId);
                    if (jwToken !== null) {
                        this.userIsLoggedIn = true;
                        if (this.doLogg)
                            console.log('jwToken', jwToken);
                    }
                };
                AppComponent.prototype.login = function (event, username, password) {
                    var _this = this;
                    this.setButton('disabled');
                    this.showErrorText = false;
                    event.preventDefault();
                    if (username == '' || password == '') {
                        this.setButton('enabled');
                        this.showErrorText = true;
                    }
                    else {
                        // If we have an existing request subscription, cancel it.
                        if (this.currentSubscription) {
                            this.currentSubscription.unsubscribe();
                        }
                        // Keep track of the response subscription so that we can cancel it in the future.
                        // Login user
                        this.currentSubscription = this._apiService
                            .login(username, password)
                            .subscribe(function (loginResponse) {
                            if (_this.doLogg)
                                console.log('loginResponse', loginResponse);
                            // Authenticate user
                            _this._apiService
                                .authenticate(loginResponse)
                                .subscribe(function (authenticateResponse) {
                                if (_this.doLogg)
                                    console.log('authenticateResponse', authenticateResponse);
                                // Set session cookie for user
                                _this._apiService
                                    .setDomainCookie(loginResponse, authenticateResponse)
                                    .subscribe(function (cookieResponse) {
                                    if (_this.doLogg)
                                        console.log('cookieResponse', cookieResponse);
                                    // Set jwToken
                                    _this._jwtService.set(_this.jwtProviderId, loginResponse);
                                    if (_this.redirectUrl != '')
                                        window.location.href = _this.redirectUrl;
                                    else
                                        window.location.reload();
                                }, function (error) {
                                    _this.setError(error);
                                });
                            }, function (error) {
                                _this.setError(error);
                            });
                        }, function (error) {
                            _this.setError(error);
                        });
                    }
                };
                AppComponent.prototype.setButton = function (type) {
                    this.buttonText = this.buttonActiveText;
                    this.isOn = true;
                    if (type == 'disabled') {
                        this.buttonText = this.buttonWaitingText;
                        this.isOn = false;
                    }
                };
                AppComponent.prototype.setError = function (error) {
                    if (error.error_description != 'undefined') {
                        this.errorText = error.error_description;
                    }
                    else if (error.error != 'undefined') {
                        this.errorText = error.error;
                    }
                    this.setButton('enabled');
                    this.showErrorText = true;
                    window.scroll(0, 0);
                    if (this.doLogg)
                        console.warn("In app.components");
                };
                AppComponent = __decorate([
                    core_5.Injectable(),
                    core_5.Component({
                        selector: "angular-sso-login-app",
                        templateUrl: pathToLoginTemplate,
                        directives: [common_1.CORE_DIRECTIVES, common_1.FORM_DIRECTIVES]
                    }), 
                    __metadata('design:paramtypes', [api_service_2.ApiService, jwt_service_1.JwtService, core_5.ElementRef, core_5.Injector])
                ], AppComponent);
                return AppComponent;
            }());
            exports_5("AppComponent", AppComponent);
        }
    }
});
System.register("custom.browser.xhr", ["angular2/core", "angular2/http", "rxjs/Rx"], function(exports_6, context_6) {
    "use strict";
    var __moduleName = context_6 && context_6.id;
    var core_6, http_2;
    var CustomBrowserXhr;
    return {
        setters:[
            function (core_6_1) {
                core_6 = core_6_1;
            },
            function (http_2_1) {
                http_2 = http_2_1;
            },
            function (_4) {}],
        execute: function() {
            CustomBrowserXhr = (function (_super) {
                __extends(CustomBrowserXhr, _super);
                function CustomBrowserXhr() {
                    _super.call(this);
                }
                CustomBrowserXhr.prototype.build = function () {
                    var xhr = _super.prototype.build.call(this);
                    xhr.withCredentials = true;
                    return (xhr);
                };
                CustomBrowserXhr = __decorate([
                    core_6.Injectable(), 
                    __metadata('design:paramtypes', [])
                ], CustomBrowserXhr);
                return CustomBrowserXhr;
            }(http_2.BrowserXhr));
            exports_6("CustomBrowserXhr", CustomBrowserXhr);
        }
    }
});
System.register("main", ["angular2/core", "angular2/platform/browser", "angular2/http", "app.component", "api.service", "api.gateway.service", "jwt.service", "custom.browser.xhr", "error.handler"], function(exports_7, context_7) {
    "use strict";
    var __moduleName = context_7 && context_7.id;
    var core_7, browser_1, http_3, app_component_1, api_service_3, api_gateway_service_3, jwt_service_2, custom_browser_xhr_1, error_handler_2;
    return {
        setters:[
            function (core_7_1) {
                core_7 = core_7_1;
            },
            function (browser_1_1) {
                browser_1 = browser_1_1;
            },
            function (http_3_1) {
                http_3 = http_3_1;
            },
            function (app_component_1_1) {
                app_component_1 = app_component_1_1;
            },
            function (api_service_3_1) {
                api_service_3 = api_service_3_1;
            },
            function (api_gateway_service_3_1) {
                api_gateway_service_3 = api_gateway_service_3_1;
            },
            function (jwt_service_2_1) {
                jwt_service_2 = jwt_service_2_1;
            },
            function (custom_browser_xhr_1_1) {
                custom_browser_xhr_1 = custom_browser_xhr_1_1;
            },
            function (error_handler_2_1) {
                error_handler_2 = error_handler_2_1;
            }],
        execute: function() {
            core_7.enableProdMode();
            browser_1.bootstrap(app_component_1.AppComponent, [
                http_3.HTTP_PROVIDERS,
                api_service_3.ApiService,
                api_gateway_service_3.ApiGateway,
                jwt_service_2.JwtService,
                error_handler_2.ErrorHandler,
                /* TODO: Use official Angular2 CORS support when merged (https://github.com/angular/angular/issues/4231). */
                core_7.provide(http_3.BrowserXhr, { useClass: custom_browser_xhr_1.CustomBrowserXhr })
            ]);
        }
    }
});
