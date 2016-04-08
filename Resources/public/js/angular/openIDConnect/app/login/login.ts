import {Component}                        from "angular2/core";
import {CORE_DIRECTIVES, FORM_DIRECTIVES} from "angular2/common";

import {HttpService}                      from 'http.service';

@Component({
    selector: "container",
    template: `
        <div class="login jumbotron center-block">
            <h1>Login</h1>
            <form role="form" (submit)="login($event, username.value, password.value)">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" #username class="form-control" id="username" placeholder="Username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" #password class="form-control" id="password" placeholder="Password">
                </div>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    `,
    /*templateUrl: "/bundles/xrowrest/js/angular/oauth2/build/login/login.html",*/
    directives: [CORE_DIRECTIVES, FORM_DIRECTIVES]
})

export class Login {
    constructor(private _httpService: HttpService) {}

    login(event, username, password) {
        event.preventDefault();
        this._httpService.login(username, password);
    }
}