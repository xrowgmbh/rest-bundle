import {Component, OnInit} from "angular2/core";
import {RouteConfig, ROUTER_DIRECTIVES} from "angular2/router";

import {Login} from "login/login";
import {Home} from "home/home";
#import {Login} from "../login/login";
#import {Home} from "../home/home";

@Component({
    selector: "app",
    templateUrl: "/bundles/xrowrest/js/angular/oauth2/build/app/app.html",
    directives: [ROUTER_DIRECTIVES]
})

@RouteConfig([
    {path: "/login", name: "Login", component: Login, useAsDefault: true},
    {path: "/home",  name: "Home",  component: Home}
])

export class AppComponent implements OnInit {
    ngOnInit() {
        console.log("Application component initialized ...");
    }
}