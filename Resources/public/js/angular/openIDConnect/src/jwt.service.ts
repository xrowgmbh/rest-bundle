import {Injectable} from "angular2/core";

@Injectable()
export class JwtService {

    public provider = "xrowapi";

    constructor () {}

    get() {
        var token = JSON.parse(localStorage.getItem(this.provider));
        return token;
    }

    set(token) {
        localStorage.setItem(this.provider, JSON.stringify(token));
        return true;
    }

    remove() {
        localStorage.removeItem(this.provider);
        return true;
    }
}