import {Component, OnInit} from "angular2/core";
import {HTTP_PROVIDERS}    from "angular2/http";

import {HttpService}       from "http.service";
import {Login}             from "login/login";
import {Home}              from "home/home";
#import {Login} from "../login/login";
#import {Home} from "../home/home";

@Component({
    selector: "app",
    template: `
        <h1 class="title">OpenID Connect Client</h1>
        <container></container>
    `,
    /*templateUrl: "/bundles/xrowrest/js/angular/oauth2/build/app/app.html"*/
    directives:[
        Login, 
        Home
    ],
    providers: [
        HTTP_PROVIDERS,
        HeroService,
    ]
})

export class AppComponent implements OnInit {
    ngOnInit() {
        console.log("Application component initialized ...");
    }
}