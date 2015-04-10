var client_id = "1_2drb6li5jocgg8w0sk4kk004osw8sw0scggwgws440kgs0sgs4",
    client_secret = "281kns8en15w80sw8oo0k0w4448w8so48w04cc0s48ggg40kk4",
    tokenURL = "http://www.wuv-abo.de.example.com/oauth/v2/token";
require(["jso/jso"], function(JSO) {
    var jsoObj = new JSO({
        client_id: client_id,
        scopes: "user",
        authorization: tokenURL
    });
    JSO.enablejQuery($);
    var token = jsoObj.checkToken();
    if(typeof token === "object" && token !== null) {
        if(token.access_token)
            showUserData();
    }
    /**
     * you need a form with id sfloginform 
     * and two login fields: username and password
     */
    $(document).ready(function(){
        $('form#sfloginform').submit( function( e ){
            e.preventDefault();
            sfLoginForm($(this), function(response){
                if(typeof response === "object"){
                    showUserData();
                } else if(typeof response === "string"){
                    jsoObj.callback(response, function(token){
                        showUserData()
                    });
                }
                window.console.log("responseQuery: ", response);
            });
        });
    });

    function sfLoginForm($form, callback){
        var request = {"grant_type": "password",
                       "scope": "user"};
        $.each( $form.serializeArray(), function(i, field) {
            request[field.name] = field.value;
        });
        request.client_id = client_id;
        request.client_secret = client_secret;
        jsoObj.getToken(function(token) {
            callback( token );
        }, request);
    }

    function showUserData(){
        jsoObj.ajax({
            url: "http://www.wuv-abo.de.example.com/xrowapi/v1/user",
            dataType: 'json',
            success: function(data) {
                // show overlay div, hide loginform
                $('#header-navigation').removeClass('open');
                $('a.dropdown-toggle').attr('aria-expanded', false);
                var outputString = "";
                for(index in data) {
                    if(typeof data[index] === "string"){
                        outputString = outputString + "<p>" + index + ":</p>" +
                                                      "<p>" + '&nbsp;&nbsp;&nbsp;&nbsp;' + data[index] + "</p>";
                    }
                    else
                    {
                        outputString = outputString + "<p>" + index + ":</p>";
                        for(index2 in data[index]) {
                            outputString = outputString + "<p>" + '&nbsp;&nbsp;&nbsp;&nbsp;' + index2 + ': ' + data[index][index2] + "</p>";
                        }
                    }
                }
                var userData = '<h2>Stammdaten</h2>' + outputString;
                //userData = userData + showAccountData();
                //userData = userData + showSubscriptionsData();
                var overlay = $('<div id="overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: #000; filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5; z-index: 10000;"> </div>');
                var htmlContent = $('<div id="userdata" style="background: #fff; border: solid 3px #ccc; color: #000; font-size: 14px; margin: 15px; padding: 5px; position: fixed; left: calc(20% - 100px); width: 455px; height: 585px; top: calc(30% - 100px); z-index: 10001;">' + userData 
                                    + 
                                    '</div>');
                overlay.appendTo(document.body);
                htmlContent.appendTo(document.body);
                overlay.click(function() { 
                    htmlContent.hide();
                    overlay.hide();
                });
                htmlContent.click(function() { 
                    htmlContent.hide();
                    overlay.hide();
                });
            }
        });
    }
    function showAccountData(){
        jsoObj.ajax({
            url: "http://www.wuv-abo.de.example.com/xrowapi/v1/account",
            dataType: 'json',
            success: function(data) {
                var outputString = "";
                for(index in data) {
                    window.console.log(typeof data[index]);
                    if(typeof data[index] === "string"){
                        outputString = outputString + "<p>" + index + ":</p>" +
                                                      "<p>" + '&nbsp;&nbsp;&nbsp;&nbsp;' + data[index] + "</p>";
                    }
                    else
                    {
                        outputString = outputString + "<p>" + index + ":</p>";
                        for(index2 in data[index]) {
                            outputString = outputString + "<p>" + '&nbsp;&nbsp;&nbsp;&nbsp;' + index2 + ': ' + data[index][index2] + "</p>";
                        }
                    }
                }
                return '<h2>Account</h2>' + outputString;
            }
        });
    }
    function showSubscriptionsData(){
        jsoObj.ajax({
            url: "http://www.wuv-abo.de.example.com/xrowapi/v1/subscriptions",
            dataType: 'json',
            success: function(data) {
                var outputString = "";
                for(index in data) {
                    window.console.log(typeof data[index]);
                    if(typeof data[index] === "string"){
                        outputString = outputString + "<p>" + index + ":</p>" +
                                                      "<p>" + '&nbsp;&nbsp;&nbsp;&nbsp;' + data[index] + "</p>";
                    }
                    else
                    {
                        outputString = outputString + "<p>" + index + ":</p>";
                        for(index2 in data[index]) {
                            outputString = outputString + "<p>" + '&nbsp;&nbsp;&nbsp;&nbsp;' + index2 + ': ' + data[index][index2] + "</p>";
                        }
                    }
                }
                return '<h2>Abos</h2>' + outputString;
            }
        });
    }
});