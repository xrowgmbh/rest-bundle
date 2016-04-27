import {Injectable} from "angular2/core";

@Injectable()
export class JwtService {

    get(name) {
        var token = JSON.parse(localStorage.getItem(name));
        return token;
    }

    set(name, token) {
        localStorage.setItem(name, JSON.stringify(token));
        return true;
    }

    remove(name) {
        localStorage.removeItem(name);
        return true;
    }
}