import {Component} from "angular2/core";
import {Router, RouterLink} from "angular2/router";
import {CORE_DIRECTIVES, FORM_DIRECTIVES} from "angular2/common";
import {Http, Headers} from "angular2/http";

import {contentHeaders} from "common/headers";
#import {contentHeaders} from "../common/headers";

@Component({
    selector: "login",
    templateUrl: "/bundles/xrowrest/js/angular/oauth2/build/login/login.html",
    directives: [RouterLink, CORE_DIRECTIVES, FORM_DIRECTIVES]
})

export class Login {
    constructor(public router: Router, public http: Http) {}

    login(event, username, password) {
        event.preventDefault();
        let body = JSON.stringify({ username, password });
        this.http.post("https://www.wuv-abo.de.example.com/angular/v1/oauth2/sessions/create", body, { headers: contentHeaders })
            .subscribe(
                response => {
                    localStorage.setItem("jwt", response.json().id_token);
                    this.router.parent.navigateByUrl("/home");
                },
                error => {
                    alert(error.text());
                    console.log(error.text());
                }
            );
    }
}