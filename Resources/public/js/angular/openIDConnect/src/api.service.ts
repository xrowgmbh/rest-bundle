import {Injectable, Inject, Injector, OpaqueToken} from "angular2/core";
import {Observable}                      from "rxjs/Observable";
import "rxjs/Rx";

import {ApiGateway} from "./api.gateway.service";

@Injectable()
export class ApiService {

    _apiSettings = oauthSettings;

    constructor (private _apiGateway: ApiGateway) {}

    /*
     * Login
     */
    login(username, password): Observable<any> {
        let loginRequestData = "grant_type=password&client_id="+this._apiSettings.client_id+"&client_secret="+this._apiSettings.client_secret+"&username="+username+"&password="+password;

        return this._apiGateway.post(this._apiSettings.baseURL+this._apiSettings.tokenURL, loginRequestData);
    }

    /*
     * Authenticate
     */
    authenticate(loginResponseData): Observable<any> {
        let authRequestData = "access_token="+loginResponseData.access_token;

        return this._apiGateway.post(this._apiSettings.baseURL+this._apiSettings.openIDConnectURL, authRequestData);
    }

    /*
     * Copy session cookie for user domain
     */
    setDomainCookie(loginResponseData, authResponseData): Observable<any> {
        return this._apiGateway.get(this._apiSettings.setcookieURL+"?access_token="+loginResponseData.access_token+"&idsv="+authResponseData.result.session_id+"");
    }
    
    /*
     * Check if user is logged in
     */
    checkSession(): Observable<any> {
        return this._apiGateway.get(this._apiSettings.baseURL+'/xrowapi/v2/storage');
    }
}