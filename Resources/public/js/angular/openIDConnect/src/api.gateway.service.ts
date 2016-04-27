import {Injectable}                                                              from "angular2/core";
import {Http, Response, RequestOptions, Headers, RequestMethod, URLSearchParams} from "angular2/http";
import {Observable}                                                              from "rxjs/Observable";
import {Subject}                                                                 from "rxjs/Subject";

import "rxjs/Rx";

export class ApiGatewayOptions {
    method: RequestMethod;
    url: string;
    headers = new Headers({ 'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept':       'application/json' });
    data: string;
}

@Injectable()
export class ApiGateway {

    // Define the internal Subject we'll use to push errors
    private errorsSubject = new Subject<any>();

    // Provide the *public* Observable that clients can subscribe to
    errors$: Observable<any>;

    // Define the internal Subject we'll use to push the command count
    private pendingCommandsSubject = new Subject<number>();
    private pendingCommandCount = 0;

    // Provide the *public* Observable that clients can subscribe to
    pendingCommands$: Observable<number>;

    constructor(
        private http: Http
    ) {
        // Create our observables from the subjects
        this.errors$ = this.errorsSubject.asObservable();
        this.pendingCommands$ = this.pendingCommandsSubject.asObservable();
    }

    get(url: string): Observable<Response> {
        let options = new ApiGatewayOptions();
        options.method = RequestMethod.Get;
        options.url = url;

        return this.request(options);
    }

    post(url: string, data: any): Observable<Response> {
        let options = new ApiGatewayOptions();
        options.method = RequestMethod.Post;
        options.url = url;
        options.data = data;

        return this.request(options);
    }


    private request(options: ApiGatewayOptions): Observable<any> {

        options.method = (options.method || RequestMethod.Get);

        let requestOptions = new RequestOptions();
        requestOptions.method = options.method;
        requestOptions.url = options.url;
        requestOptions.headers = options.headers;
        requestOptions.body = options.data;

        let isCommand = (options.method !== RequestMethod.Get);

        if (isCommand) {
            this.pendingCommandsSubject.next(++this.pendingCommandCount);
        }

        let stream = this.http.request(options.url, requestOptions)
            .catch((error: any) => {
                this.errorsSubject.next(error);
                return Observable.throw(error);
            })
            .map(this.unwrapHttpValue)
            .catch((error: any) => {
                return Observable.throw(this.unwrapHttpError(error));
            })
            .finally(() => {
                if (isCommand) {
                    this.pendingCommandsSubject.next(--this.pendingCommandCount);
                }
            });

        return stream;
    }

    private extractValue(collection: any, key: string): any {
        var value = collection[key];
        delete (collection[key]);
        return value;
    }

    private unwrapHttpError(error: any): any {
        try {
            return (error.json());
        } catch (jsonError) {
            return ({
                code: -1,
                message: "An unexpected error occurred."
            });
        }
    }

    private unwrapHttpValue(value: Response): any {
        return (value.json());
    }
}