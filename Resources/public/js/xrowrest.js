$.ajax({
    type    : 'get',
    url     : '/getparams/client',
}).done(function (data) {
    var client_id = data.client_id,
        client_secret = data.client_secret,
        mainUrl = window.location.hostname,
        tokenURL = "/oauth/v2/token",
        authURL = "/xrowapi/v1/auth",
        apiUserURL = "/xrowapi/v1/user";
    require(["jso/jso"], function(JSO) {
        var jsoObj = new JSO({
            client_id: client_id,
            scopes: "user",
            authorization: authURL
        });
        JSO.enablejQuery($);
        var token = jsoObj.checkToken();
        if(typeof token === "object" && token !== null) {
            if(token.access_token) {
                $('#header-navigation a.dropdown-toggle').attr('href', apiUserURL).attr('data-toggle', '');
                $('#header-navigation div.dropdown-menu.dropdown-login').remove();
            }
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
                        window.location = apiUserURL;
                    } else if(typeof response === "string"){
                        var queryHash = "#" + response.split("?"); 
                        jsoObj.callback(queryHash, function(token){
                            window.location = apiUserURL;
                        });
                    }
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
            $.ajax({
                type    : 'post',
                url     : tokenURL,
                data    : request
            }).done(function (requestData) {
                jsoObj.getToken(function(data) {
                    callback( data );
                }, requestData);
            });
        }

        function showUserData(){
            jsoObj.ajax({
                url: mainUrl + apiUserURL,
                dataType: 'json',
            }).done(function (data) {
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
            });
        }
    });
}).fail(function (jqXHR, textStatus) {
    alert("An unexpeded error occured in xrowrest.js.");
});