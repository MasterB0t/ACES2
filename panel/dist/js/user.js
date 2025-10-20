var IDLETIME = 0;

function func() {

    if(idleTime < 5 ) {
        idleTime = idleTime + 1;
        return;
    }

    idleTime = 0;

    $.ajax({
        url: '/user/ajax/pUser.php',
        type: 'post',
        dataType: 'json',
        data: {action : 'is_logged'},
        error: function (xhr) {

            switch (xhr.status) {
                case 401:
                case 403:
                    window.location = '/user/lock.php?ref=' + window.location.pathname + window.location.search ;
                    break;
                default :
                    var response = xhr.responseJSON;
                    if (typeof response != "undefined" && typeof response.error != "undefined")
                        toastr.error(response.error);
                    else
                        toastr.error("System Error");

            }
        }
    });

}

$(document).ready(function(){

    setPreferences();
    GetUserCredits();
    GetNotifications('user');

    setInterval(func, 10000);

    // Zero the idle timer on mouse movement.
    $(this).mouseout(function (e) {
        idleTime = 0;
    });
    $(this).keypress(function (e) {
        idleTime = 0;
    });


    $("body").fadeIn();

});

$( window ).on( "resize", function() {
    if($(".select2").length)
        $(".select2").select2();
});