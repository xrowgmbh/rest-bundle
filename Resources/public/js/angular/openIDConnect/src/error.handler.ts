import {Injectable}            from "angular2/core";
import {Subject}               from "rxjs/Subject";

import {ApiGateway}            from "./api.gateway.service";
import {ApiService}            from "./api.service";

@Injectable()
export class ErrorHandler {

    constructor(private _apiGateway: ApiGateway, _apiService: ApiService) {

        _apiGateway.errors$.subscribe(
            (value: any) => {
                console.group("HttpErrorHandler");
                console.log(value.status, "status code detected.");
                console.dir(value);
                console.groupEnd();
            });
    }
}