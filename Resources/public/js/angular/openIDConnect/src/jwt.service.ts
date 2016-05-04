import {Injectable} from "angular2/core";

@Injectable()
export class JwtService {

    get(name) {
        var token = JSON.parse(localStorage.getItem(name));
        return token;
    }

    set(name, token) {
        // If token is refreshed
        if (typeof token.refresh_token == 'undefined') {
            var oldJwToken = this.get(name);
            if (typeof oldJwToken.refresh_token != 'undefined') {
                token.refresh_token = oldJwToken.refresh_token;
            }
        }
        var jwToken = JSON.stringify(token);
        localStorage.setItem(name, jwToken);
        return true;
    }

    remove(name) {
        localStorage.removeItem(name);
        return true;
    }
}
