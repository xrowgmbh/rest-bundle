import {Component, Injectable} from "angular2/core";
import {CORE_DIRECTIVES}       from "angular2/common";

@Injectable()
@Component({
    selector: "user-data",
    template: `
        <ul>
            <li>
            </li>
          </ul>`,
    directives: [CORE_DIRECTIVES]
})

export class UserData {

}