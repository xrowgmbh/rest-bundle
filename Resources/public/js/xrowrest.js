/**
 * you need a form with id sfloginform 
 * and two login fields: username and password
 */
$(document).ready(function(){
    $('form#sfloginform').submit( function( e ){
        e.preventDefault();
        sfLoginForm( $(this), function( response ){
            window.console.log(JSON.stringify(response));
        });
        return false;
    });
});


function sfLoginForm( $form, callback ){
    var values = {};
    $.each( $form.serializeArray(), function(i, field) {
        values[field.name] = field.value;
    });
    var jso = new JSO({
        /* @ToDo: reset vars client_id, client_secret, authorization with dynamic values from parameters.yml */
        client_id: "1_2drb6li5jocgg8w0sk4kk004osw8sw0scggwgws440kgs0sgs4",
        client_secret: "281kns8en15w80sw8oo0k0w4448w8so48w04cc0s48ggg40kk4",
        authorization: "https://abo.example.com/oauth/v2/token",
        scope: "user",
        grant_type: "password",
        username: values.username,
        password: values.password
    });
    JSO.enablejQuery($);
    jso.ajax({
        url: "https://abo.example.com/xrowapi/v1/user",
        dataType: 'json',
        success: function(data) {
            console.log("Response (me):");
            console.log(data);
            $(".loader-hideOnLoad").hide();
        }
    });
}