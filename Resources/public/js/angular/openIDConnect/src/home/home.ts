import {Component} from "angular2/core";
import {CORE_DIRECTIVES} from "angular2/common";
import {Http, Headers} from "angular2/http";
import {AuthHttp} from "angular2-jwt";
import {Router} from "angular2/router";

@Component({
    selector: "home",
    templateUrl: "/bundles/xrowrest/js/angular/oauth2/build/home/home.html",
    directives: [CORE_DIRECTIVES]
})

export class Home {
  jwt: string;
  decodedJwt: string;
  response: string;
  api: string;

  constructor(public router: Router, public http: Http, public authHttp: AuthHttp) {
    var window: any = window;
    this.jwt = localStorage.getItem("jwt");
    this.decodedJwt = this.jwt && window.jwt_decode(this.jwt);
  }

  logout() {
    localStorage.removeItem("jwt");
    this.router.parent.navigateByUrl("/login");
  }

  callAnonymousApi() {
    this._callApi("Anonymous", "https://www.wuv-abo.de.example.com/angular/v1/oauth2/api/random-quote");
  }

  callSecuredApi() {
    this._callApi("Secured", "https://www.wuv-abo.de.example.com/angular/v1/oauth2/api/protected/random-quote");
  }

  _callApi(type, url) {
    this.response = null;
    if (type === "Anonymous") {
      // For non-protected routes, just use Http
      this.http.get(url)
        .subscribe(
          response => this.response = response.text(),
          error => this.response = error.text()
        );
    }
    if (type === "Secured") {
      // For protected routes, use AuthHttp
      this.authHttp.get(url)
        .subscribe(
          response => this.response = response.text(),
          error => this.response = error.text()
        );
    }
  }
}