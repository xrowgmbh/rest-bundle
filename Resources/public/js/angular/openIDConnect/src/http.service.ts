import {Injectable, Inject}                      from 'angular2/core';
import {Http, Response, Headers, RequestOptions} from 'angular2/http';
import {Observable}                              from 'rxjs/Observable';
import 'rxjs/Rx';

//import {AppComponent}                            from "../app/app.component";
import {JwtService}                              from "../app/jwt.service";
import {CastResponseToOobject}                   from "../app/cast.response.to.object";

@Injectable()
export class HttpService {

    _oauthSettings    = oauthSettings;
    _userUrl          = this._oauthSettings.endpointPrefix+'/user';
    _accountUrl       = this._oauthSettings.endpointPrefix+'/account';
    _subscriptionsUrl = this._oauthSettings.endpointPrefix+'/subscriptions';
    _headerOptions    = new RequestOptions({ headers: new Headers({ 'Content-Type': 'application/x-www-form-urlencoded',
                                                                            'Accept':       'application/json' }) });

    responseUser;
    responseAccount;
    responseSubscriptions;

    errorText;
    errorStatus;

    constructor (public _http: Http, private _jwtService: JwtService) {}

    /*
     * Authenticate
     */
    authenticate(loginResponseData) {
        let authRequestData = "access_token="+loginResponseData.access_token;
        this._http
            .post(this._oauthSettings.baseURL+this._oauthSettings.openIDConnectURL, authRequestData, this._headerOptions)
            .subscribe(
                authResponse => {
                    this.setDomainCookie(authResponse.json(), loginResponseData);
                },
                errorAuthResponse => this.handleError(errorAuthResponse, 'AUTH FEHLER: ')
            );
    }

    /*
     * Set locale cookie for user domain
     */
    setDomainCookie(authResponseData, loginResponseData) {
        let castedAuthResponse = new CastResponseToOobject(authResponseData);
        this._http
            .get(this._oauthSettings.setcookieURL+"?access_token="+loginResponseData.access_token+"&idsv="+castedAuthResponse.result.session_id+"")
            .subscribe(
                setCookieResponse => {
                    this._jwtService.set(loginResponseData);
                    window.location.reload();
                },
                errorSetCookieResponse => this.handleError(errorSetCookieResponse, 'SETCOOKIE FEHLER: ')
            );
    }

    /*
     * Get user and account data from CRM
     */
    getUserData(accessToken) {
        let accessTokenRequestData = "?access_token="+accessToken;

        return Observable.forkJoin(
            this._http
                .get(this._oauthSettings.baseURL+this._userUrl+accessTokenRequestData, this._headerOptions)
                .map((res:Response) => this.responseUser = res.json()),
            this._http
                .get(this._oauthSettings.baseURL+this._accountUrl+accessTokenRequestData, this._headerOptions)
                .map((res:Response) => this.responseAccount = res.json())
        );
    }

    /*
     * Refresh the access_token with the refresh_token
     */
    refreshToken() {
        let jwToken = this._jwtService.get();
        if (jwToken !== null && typeof jwToken.refresh_token != "undefined") {
            let refreshTokenRequestData = "grant_type=refresh_token&client_id="+this._oauthSettings.client_id+"&client_secret="+this._oauthSettings.client_secret+"&refresh_token="+jwToken.refresh_token;
            this._http
                .post(this._oauthSettings.baseURL+this._oauthSettings.tokenURL, refreshTokenRequestData, this._headerOptions)
                .subscribe(
                    refreshTokenResponse => {
                        this._jwtService.set(refreshTokenResponse.json());
                        window.location.reload();
                    },
                    errorRefreshTokenResponse => this.handleError(errorRefreshTokenResponse, 'refreshToken FEHLER: ')
                );
        }
        else {
            console.log('refreshToken FEHLER: jwToken oder jwToken.refresh_token ist nicht gesetzt.', jwToken);
        }
    }

    /*
     * Logout the user
     */
    logout() {
        let jwToken = this._jwtService.get();
        if (jwToken !== null && typeof jwToken.access_token != "undefined") {
            this._http
                .get(this._oauthSettings.baseURL+this._oauthSettings.apiSessionURL+"?access_token="+jwToken.access_token, this._headerOptions)
                .subscribe(
                    sessionResponse => {
                        if (sessionResponse.status == 200) {
                            let castedSessionResponse = new CastResponseToOobject(sessionResponse.json());
                            //console.log('castedSessionResponse', castedSessionResponse);
                            //return true;
                            this._http
                                .delete(this._oauthSettings.baseURL+this._oauthSettings.apiLogoutURL+'/'+castedSessionResponse.session_id, this._headerOptions)
                                .subscribe(
                                    logoutResponse => {
                                        this._jwtService.remove();
                                        window.location.reload();
                                    },
                                    errorLogoutResponse => this.handleError(errorLogoutResponse, 'Logout FEHLER: ')
                                );
                        }
                    },
                    errorSessionResponse => this.handleError(errorSessionResponse, 'Logout Session FEHLER: ')
                );
        }
        else {
            console.log('LOGOUT FEHLER: jwToken oder jwToken.access_token ist nicht gesetzt.', jwToken);
        }
    }

    public handleError(error: Response, message) {
        //console.error(message, error.json());
        throw Error(error.json());
    }
}