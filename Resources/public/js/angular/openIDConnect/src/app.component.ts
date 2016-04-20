import {Component, Injectable, OnInit, Input} from "angular2/core";
import {HTTP_PROVIDERS}                       from "angular2/http";
import {CORE_DIRECTIVES, FORM_DIRECTIVES}     from "angular2/common";
import {Observable}                           from "rxjs/Observable";
import 'rxjs/Rx';

import {HttpService}                          from "./http.service";
import {JwtService}                           from "./jwt.service";
import {CastResponseToOobject}                from "./cast.response.to.object";

@Injectable()
@Component({
    selector: "app",
    templateUrl: "/bundles/xrowrest/js/angular/openIDConnect/build/app.html",
    directives: [CORE_DIRECTIVES, FORM_DIRECTIVES],
    providers: [
        HTTP_PROVIDERS,
        HttpService,
        JwtService
    ]
})

export class AppComponent implements OnInit{

    userData;
    accountData;
    showUserData: Boolean;
    errorText: string;
    showErrorText: Boolean;
    loginDataEmpty: string;

    constructor(private _httpService: HttpService, private _jwtService: JwtService) {
        this.showUserData = false;
        this.showErrorText = false;
        this.loginDataEmpty = 'Bitte geben Sie Ihren Benutzernamen und Ihr Passwort ein.';
    }

    ngOnInit() {
        console.log("Application component initialized ...");
        // Get the JWT
        let jwToken = this._jwtService.get();
        if (jwToken !== null) {
            this.showUserData = true;
            this._httpService.getUserData(jwToken.access_token)
                .subscribe(
                    response => {
                        if (typeof this._httpService.responseUser != "undefined") {
                            let castedUser = new CastResponseToOobject(this._httpService.responseUser);
                            if (castedUser.result === "undefined") {
                                this.handleErrors(castedUser);
                            }
                            else {
                                this.userData = castedUser.result;
                            }
                        }
                        if (typeof this._httpService.responseAccount != "undefined") {
                            let castedAccount = new CastResponseToOobject(this._httpService.responseAccount);
                            if (castedAccount.result === "undefined") {
                                this.handleErrors(castedAccount);
                            }
                            else {
                                this.accountData = castedAccount.result;
                            }
                        }
                    },
                    errorResponse => {
                        this.handleErrors(errorResponse);
                    }
                );
            //this.jwtService.checkSessionIframe(jwToken);
        }
    }

    login(event, username, password) {
        event.preventDefault();
        this.showErrorText = false;
        this.errorText = '';
        if (username == '' || password == '') {
            this.errorText = this.loginDataEmpty;
            this.showErrorText = true;
        }
        else {
            let loginRequestData = "grant_type=password&client_id="+this._httpService._oauthSettings.client_id+"&client_secret="+this._httpService._oauthSettings.client_secret+"&username="+username+"&password="+password;
            this._httpService._http
                .post(this._httpService._oauthSettings.baseURL+this._httpService._oauthSettings.tokenURL, loginRequestData, this._httpService._headerOptions)
                .subscribe(
                    loginResponse => {
                        this._httpService.authenticate(loginResponse.json());
                    },
                    errorLoginResponse => {
                        let castedLoginResponse = new CastResponseToOobject(errorLoginResponse);
                        this.handleErrors(castedLoginResponse);
                    }
                );
        }
    }

    logout(event) {
        event.preventDefault();
        this._httpService.logout();
    }

    handleErrors(errorResponse) {
        let errorJson = errorResponse.json();
        let errorStatus = errorResponse.status;
        let error = new CastResponseToOobject(errorJson);

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
    }
}