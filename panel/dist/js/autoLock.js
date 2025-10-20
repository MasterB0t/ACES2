

var AutoLock = localStorage.getItem('ADMIN_SETTINGS.AUTO_LOCK_IN');
var idleTime = 0;



function timerIncrement() {

    if( AutoLock == null )
        AutoLock = 5;

    idleTime = idleTime + 1;
    if (AutoLock > 0 && idleTime > AutoLock ) { // minutes
        window.location = '/admin/lock.php?ref=' + window.location.pathname + window.location.search ;
    }
}

$(document).ready(function(){

    // Increment the idle time counter every minute.
    var idleInterval = setInterval(timerIncrement, 60000); // 1 minute 60000

    // Zero the idle timer on mouse movement.
    $(this).mouseout(function (e) {
        idleTime = 0;
    });
    $(this).keypress(function (e) {
        idleTime = 0;
    });

});