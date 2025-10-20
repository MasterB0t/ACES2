var idleTime = 0;


function timerIncrement() {

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

    // Increment the idle time counter every minute.
    var idleInterval = setInterval(timerIncrement, 10000);

    // Zero the idle timer on mouse movement.
    $(this).mouseout(function (e) {
        idleTime = 0;
    });
    $(this).keypress(function (e) {
        idleTime = 0;
    });

});