import {Component, Injectable, Injector, OnInit, ElementRef} from "angular2/core";
import {CORE_DIRECTIVES, FORM_DIRECTIVES}                    from "angular2/common";
import {Observable}                                          from "rxjs/Observable";
import {Subscription}                                        from "rxjs/Subscription";
import "rxjs/Rx";

import {ApiService}   from "./api.service";
import {JwtService}   from "./jwt.service";
import {ErrorHandler} from "./error.handler";

@Injectable()
@Component({
    selector: "angular-sso-login-app",
    templateUrl: pathToLoginTemplate,
    directives: [CORE_DIRECTIVES, FORM_DIRECTIVES]
})

export class AppComponent implements OnInit{

    private currentSubscription: Subscription;

    showErrorText: Boolean = false;
    userIsLoggedIn: Boolean = false;
    errorText: string = 'Please fill in required data.';
    isOn: Boolean;
    buttonText: string = 'Login';
    buttonActiveText: string = 'Login';
    buttonWaitingText: string = 'Please wait...';
    redirectUrl: string = '';
    doLogg: Boolean = false;
    jwtProviderId: string = 'xrow-rest-token';
    lsKeyName: string = 'xrowOpenIDConnect';

    constructor(
        private _apiService: ApiService,
        private _jwtService: JwtService,
        private _elRef: ElementRef,
        injector: Injector
    ) {
        injector.get(ErrorHandler);

        // Enable button
        this.setButton('enabled');

        // Set login empty fields error if exists
        if (_elRef.nativeElement.getAttribute('errorLoginEmptyfields') != '')
            this.errorText = _elRef.nativeElement.getAttribute('errorLoginEmptyfields');

        // Set redirect url if exists
        if (_elRef.nativeElement.getAttribute('redirectAfterApiLogin') !== null && _elRef.nativeElement.getAttribute('redirectAfterApiLogin') != '') {
            this.redirectUrl = _elRef.nativeElement.getAttribute('redirectAfterApiLogin');
            if (!this.redirectUrl.match(/^http/) && !this.redirectUrl.match(/^\//)) {
                this.redirectUrl = '/'+this.redirectUrl;
            }
        }

        if (_elRef.nativeElement.getAttribute('buttonActiveText') !== null && _elRef.nativeElement.getAttribute('buttonActiveText') != '')
            this.buttonActiveText = _elRef.nativeElement.getAttribute('buttonActiveText');

        if (_elRef.nativeElement.getAttribute('buttonWaitingText') !== null && _elRef.nativeElement.getAttribute('buttonWaitingText') != '')
            this.buttonWaitingText = _elRef.nativeElement.getAttribute('buttonWaitingText');

        if (_elRef.nativeElement.getAttribute('jwtProviderId') !== null && _elRef.nativeElement.getAttribute('jwtProviderId') != '')
            this.jwtProviderId = _elRef.nativeElement.getAttribute('jwtProviderId');
    }

    ngOnInit() {
        // Get the JWT
        let jwToken = this._jwtService.get(this.jwtProviderId);
        if (jwToken !== null) {
            this.userIsLoggedIn = true;
            if (this.doLogg)
                console.log('jwToken', jwToken);
        }
    }

    login(event, username, password) {
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
                .subscribe(
                    loginResponse => {
                        if (this.doLogg)
                            console.log('loginResponse', loginResponse);
                        // Authenticate user
                        this._apiService
                            .authenticate(loginResponse)
                            .subscribe(
                                authenticateResponse => {
                                    if (this.doLogg)
                                        console.log('authenticateResponse', authenticateResponse);
                                    // Set session cookie for user
                                    this._apiService
                                        .setDomainCookie(loginResponse, authenticateResponse)
                                        .subscribe(
                                            cookieResponse => {
                                                if (this.doLogg)
                                                    console.log('cookieResponse', cookieResponse);
                                                // Set jwToken
                                                this._jwtService.set(this.jwtProviderId, loginResponse);
                                                if (this.redirectUrl != '')
                                                    window.location.href = this.redirectUrl;
                                                else
                                                    window.location.reload();
                                            },
                                            (error: any) => {
                                                this.setError(error);
                                            });
                                },
                                (error: any) => {
                                    this.setError(error);
                                });
                    },
                    (error: any) => {
                        this.setError(error);
                    });
        }
    }

    setButton(type) {
        this.buttonText = this.buttonActiveText;
        this.isOn = true;
        if (type == 'disabled') {
            this.buttonText = this.buttonWaitingText;
            this.isOn = false;
        }
    }

    setError(error) {
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
    }
}