function adminGetSettings(successCall=null) {

    $.get( '/admin/ajax/pAdmin.php?action=get_settings' , function(resp) {

        $.each(resp.data, function (name, value) {
            localStorage.setItem("ADMIN_SETTINGS."+name, value);
        });

        if(typeof successCall == 'function')
            successCall();

    }, 'json');

}

function ToggleBouquets(v) {

    if (v) {
        $("input[name='bouquets[]']").prop('checked', true);
    } else {
        $("input[name='bouquets[]']").prop('checked', false);
    }
}

function GetUserCredits() {
    $.ajax({
        url: '/user/ajax/pUser.php',
        type: 'post',
        dataType: 'json',
        data: { action: 'get_credits'},
        success: function (resp) {

            $(".badgeUserCredits").fadeOut( function() {
                $(".badgeUserCredits").html(resp.data.credits);
                $(".UserCredits").html("Credits "+resp.data.credits);
                $(".badgeUserCredits").fadeIn();
            });

        }, error: function (xhr) {

            switch (xhr.status) {
                case 401:
                case 403:
                    window.location.reload();
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

function setPreferences() {

    if(localStorage.getItem('dark_mode') == 'true' ) {

        $('body').addClass('dark-mode');
        $('nav.main-header').removeClass('navbar-white').addClass('navbar-dark');
    } else {
        $('body').removeClass('dark-mode');
        $('nav.main-header').removeClass('navbar-dark').addClass('navbar-white');
    }

    if(localStorage.getItem('text-size')) {

        val = localStorage.getItem('text-size');

        $("body").removeClass('text-xs');
        $("body").removeClass('text-sm');
        $("body").removeClass('text-xl');
        $("body").removeClass('text-lg');

        switch(val) {
            case 'very_small':
                $("body").addClass('text-xs');
                break;

            case 'small':
                $("body").addClass('text-sm');
                break;

            case 'big':
                $("body").addClass('text-lg');
                break;


        }
    }
}

function MODAL(url,options = {},successCall) {

    var modal_id = 'Modal';
    var cSize = 'modal-sm';

    width = '900px';
    c_width = 'modal-lg';

    if($( document ).width() < 1000 ) {
        width = $( document ).width() - ($( document ).width() * 0.10);
    }


    if(typeof options == 'string') modal_id = options
    else if(typeof options == 'object') {
        modal_id = (options.modal_id) ? options.modal_id : 'Modal';
        if(options.size == 'small') width = '300px';
        else if(options.size == 'medium') width = '600px';
        else if(options.size == 'large') { c_width= 'modal-lg'; width = '900px'; }
        else if(options.size == 'xlarge') { c_width= 'modal-xl'; width = '1200px'; }
        else if(options.size == 'full')  width = '98%';
    }else {

    }

    var backdrop = '';
    if(options.backdrop == 'static')
        backdrop = 'data-backdrop="static"'

    if(modal_id.indexOf('#') == -1 )
        modal_id = "#"+modal_id;

    modal_id = modal_id + Date.now();

    // if($('.modal').hasClass('in')) {
    //     $(modal_id).modal('hide');
    //     $(modal_id+" .modal-dialog .modal-content").html('');
    // }


    $('.cmodal').each(function(i, obj) {

        if($(obj).hasClass('in'))
            $(obj).modal('hide');
        $(obj).remove();

    });
    $(".modal-backdrop").remove();

    if($(modal_id).length)
        $(modal_id).remove();

    if(!$(modal_id).length) {
        mid = modal_id.replace('#', '');


        $("section.content").prepend(
            ' <div id="' + mid + '" class="modal fade cmodal" '+backdrop+'>' +
            '                        <div style="width:'+width+'" class="modal-dialog '+c_width+'">' +
            '                            <div class="modal-content">' +
            '                            </div>' +
            '                        </div>' +
            '                    </div>');


    }

    $.get( url , function( data ) {

        $(modal_id+" .modal-dialog .modal-content").html(data);
        $(modal_id).modal('show');
        if(typeof successCall == 'function' ) successCall();


    }).fail( function(x){
        if(x.status == 401 ) location.reload();
        else if( x.status == 403 ) alert("You don't have permissions to perform this action.");
        else if ( x.getResponseHeader('Error-Msg') != null ) alert( x.getResponseHeader('Error-Msg') );
        else alert("System Error.");
        $(modal_id).modal('hide');
    });

    return false;

}

function GetNotifications(type = 'admin') {

    $("#notifications-dropdown").html(
        '<span class="dropdown-item dropdown-header"> 0 Notifications</span>' +
        '<div class="dropdown-divider"></div>' +
        '<a href="#" style="padding-bottom:24px;" class="dropdown-item dropdown-footer"></a>');

    $.ajax({

        url: '/'+type+'/ajax/pNotifications.php',
        type: 'post',
        dataType: 'json',
        data: {action : 'get_notifications'},
        success: function (resp) {

            if(resp.notifications.length < 1) {
                $(".notification-badge").html('');
                return;
            }

            $(".notification-badge").html(resp.notifications.length);
            $("#notifications-dropdown .dropdown-header").html(resp.notifications.length + ' Notifications' );

            for(i=0;resp.new_notifications.length > i; i++) {
                toastr.success(resp.new_notifications[i]);
            }

            link = '';

            for(i=0; resp.notifications.length > i; i++ ) {
                not = resp.notifications[i];
                $("#notifications-dropdown").find('.dropdown-divider:last').after('<a class="dropdown-item" ' +
                    ' not-id="'+not.id+'" not-link="'+link+'" not-type="'+type+'" >' +
                    '<i class="fas ' + not.icon + ' mr-2"></i> ' + not.message +
                    '<span class="float-right text-muted text-sm notification-time"> '+not.date+' </span>' +
                    '</a> ' +
                    '<div class="dropdown-divider"></div>');
            }

        }
    });
}

$(document).ready(function() {

    $(document).on('click', '#notifications-dropdown a', function() {

        var type = $(this).attr('not-type')
        var not_id = $(this).attr('not-id')
        var link = $(this).attr('not-link');
        var msg = $(this).text();

        $(this).fadeOut();

        $.ajax({
            url: '/'+type+'/ajax/pNotifications.php',
            type: 'post',
            dataType: 'json',
            data: {'action' : 'remove_notification', 'notification_id' : not_id },
            success: function (resp) {
                if (link != '')
                    window.location.href = link
                else {
                    toastr.success(msg);
                    GetNotifications(type);
                }

            }
        });

    })

});

