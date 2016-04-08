import {Injectable}                              from 'angular2/core';
import {Http, Response, Headers, RequestOptions} from 'angular2/http';
import {Observable}                              from 'rxjs/Observable';

import {contentHeaders}     from "common/headers";

@Injectable()
export class HttpService {
    constructor (private http: Http) {}

    private _clientId              = '1_49cgf41l9u80wo0g0sggw80wk8cc0cwsss8skk0cs0kcg8so40',
    private _clientSecret          = '5xltt114rhs8o0c0sc088wkccg4o0ww04sk004kg8wgkos4w8s',
    private _baseUrl               = 'https://api.wuv.de.example.com/angular/v1';
    private _tokenUrl              = '/token';
    private _authUrl               = '/auth';
    private _openIDConnectUrl      = '/oicauth';
    private _apiSessionUrl         = '/session';
    private _apiLogoutUrl          = '/sessions';
    private _setcookieUrl          = '/setcookie';
    private _checkSessionIframeUrl = '/check_session_iframe';
    private _userUrl               = '/user';
    private _accountUrl            = '/account';
    private _subscriptionsUrl      = '/subscriptions';

    let headers = new Headers({ 'Content-Type': 'application/json',
                                'Accept':       'application/json' });
    let options = new RequestOptions({ headers: headers });

    login(username, password) {
        console.log('username: '+username+', password: '+password);
        console.log('oauthSettings', oauthSettings);
        let body = JSON.stringify({ this._clientId, this._clientSecret, username, password });

        this.http.post(this._baseUrl+_tokenUrl, body, options)
            .subscribe(
                response => {
                    //localStorage.setItem("xrowapi_jwt", response.json().id_token);
                    console.log('response', response);
                },
                error => {
                    alert(error.text());
                    console.log(error.text());
                }
            )
            .catch(this.handleError);
    }

    private handleError (error: Response) {
        // in a real world app, we may send the error to some remote logging infrastructure
        // instead of just logging it to the console
        console.error(error);
        return Observable.throw(error.json().error || 'Server error');
    }
}