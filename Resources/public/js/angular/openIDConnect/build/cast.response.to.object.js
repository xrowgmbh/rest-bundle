System.register([], function(exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var CastResponseToOobject;
    return {
        setters:[],
        execute: function() {
            /**
             *
             * Workaround for compiling error
             *    error TS2339: Property 'result' does not exist on type 'Response'.
             * or error TS2339: Property 'session_id' does not exist on type 'Response'.
             *
             */
            CastResponseToOobject = (function () {
                function CastResponseToOobject(Response) {
                    return Response;
                }
                return CastResponseToOobject;
            }());
            exports_1("CastResponseToOobject", CastResponseToOobject);
        }
    }
});

//# sourceMappingURL=cast.response.to.object.js.map
