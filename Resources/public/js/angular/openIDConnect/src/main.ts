import {provide, enableProdMode}    from "angular2/core";
import {bootstrap}                  from "angular2/platform/browser";
import {HTTP_PROVIDERS, BrowserXhr} from "angular2/http";

import {AppComponent}     from "./app.component";
import {ApiService}       from "./api.service";
import {ApiGateway}       from "./api.gateway.service";
import {JwtService}       from "./jwt.service";
import {CustomBrowserXhr} from "./custom.browser.xhr";
import {ErrorHandler}     from "./error.handler";

enableProdMode();
bootstrap(
    AppComponent, [
        HTTP_PROVIDERS,
        ApiService,
        ApiGateway,
        JwtService,
        ErrorHandler,
        /* TODO: Use official Angular2 CORS support when merged (https://github.com/angular/angular/issues/4231). */
        provide(BrowserXhr, { useClass: CustomBrowserXhr })
    ]
);