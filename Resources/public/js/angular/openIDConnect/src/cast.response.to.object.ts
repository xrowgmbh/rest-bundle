/**
 *
 * Workaround for compiling error 
 *    error TS2339: Property 'result' does not exist on type 'Response'.
 * or error TS2339: Property 'session_id' does not exist on type 'Response'.
 *
 */
export class CastResponseToOobject {
    public result;
    public session_id;
    public error;
    public error_type;
    public error_description;

    constructor(Response) {
        return Response;
    }
}