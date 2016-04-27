// TODO: Use official Angular2 CORS support when merged (https://github.com/angular/angular/issues/4231).
import {Component, Injectable} from "angular2/core";
import {BrowserXhr}            from "angular2/http";
import "rxjs/Rx";

@Injectable()
export class CustomBrowserXhr extends BrowserXhr {
    constructor() {
        super();
    }
    build(): any {
        let xhr = super.build();
        xhr.withCredentials = true;
        return <any>(xhr);
    }
}