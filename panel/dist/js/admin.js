
var _IDLETIME = 0;

$("#sidebar-toggle").click(function() {
    c = !$("body").hasClass('sidebar-collapse');
    localStorage.setItem('admin.sidebar_collapse', c );
});

function PlayInPlayer(type,id) {

    query = "";
    if(type == 'stream' )
        query +=  "stream_id="+id
    else
        query += "file_id="+id

    $.ajax({
        url: "/admin/IPTV/build_link.php?"+query,
        type: 'post',
        dataType: 'json',
        data: $(this).serialize(),
        success: function (resp) {
            window.location.href = "vlc://"+resp.url;
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

function _ISADMINLOG() {
    if(_IDLETIME < 5 ) {
        _IDLETIME = _IDLETIME + 1;
        return;
    }

    _IDLETIME = 0;

    $.ajax({

        url: '/admin/ajax/pAdmin.php',
        type: 'post',
        dataType: 'json',
        data: {action : 'is_logged'},
        error: function (xhr) {

            switch (xhr.status) {
                case 401:
                case 403:
                    window.location = '/admin/lock.php?ref=' + window.location.pathname + window.location.search ;
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

function GETPROGRESS(process) {

    $.ajax({
        url: 'ajax/pProcess.php',
        type: 'post',
        dataType: 'json',
        data: { process : process },
        success: function (resp) {

            htm = '';

            htm += '<div class="info-box bg-info ">' +
                '<span class="info-box-icon"><i class="fa fa-download"></i></span>' +
                '<div class="info-box-content">' +
                '<span class="info-box-text">Importing </span>' +
                ' <span class="info-box-number"></span>' +

                '<div class="progress">' +
                '<div class="progress-bar" style="width:50%"></div>' +
                '</div>' +
                '<span class="progress-description">' +
                '<a onclick="#" href=#!> Stop Import </a>' +
                '</span>' +
                '</div>' +
                '</div>';

            $(".row-progress").append(htm);

        }, error: function (xhr) {

            switch (xhr.status) {
                case 401:
                case 403:
                    window.location.reload();
                    break;
            }
        }
    });
}

var TIMEOUT_GLOBAL_SEARCH;
function UpdateGlobalSearchList(input_obj) {

    if(TIMEOUT_GLOBAL_SEARCH)
        clearTimeout(TIMEOUT_GLOBAL_SEARCH);

    var value = input_obj.value;

    if(value.length < 3)
        return false;

    TIMEOUT_GLOBAL_SEARCH = setTimeout(function() {

        $("#global-search-results").html('');

        $.ajax({
            url: '/admin/ajax/global_search.php',
            type: 'post',
            dataType: 'json',
            data: { 'search': value },
            success: function(data) {

                for(var i=0; i < data.length ; i++ ) {

                    opt = data[i];

                    $("#global-search-results").prepend("<li><a href='"+opt.link+"'> " +
                        opt.title +
                        "<span>"+ opt.name + "</span>" +
                    "</a></li>");

                }

                if( $("#global-search-results li").length > 0 )
                    $("#global-search-results").show();
            }
        });

    }, 500 );


}

$(document).ready(function(){

    //GLOBAL SEARCH
    $("#global-search").submit(function(){ return false; });

    $("#input-global-search").focus(function(){
       if( $("#global-search-results li").length > 0 )
           $("#global-search-results").show();
    })

    $("#input-global-search").blur(function() {
        setTimeout(function() { $("#global-search-results").fadeOut(); },200)
    })

    //AUTOLOCK.
    setInterval(_ISADMINLOG, 1000);
    $(this).mouseout(function (e) {
        _IDLETIME = 0;
    });
    $(this).keypress(function (e) {
        _IDLETIME = 0;
    });

    //ACTIVE SIDEBAR LINK
    //var scriptName=location.href;
    // scriptName = scriptName.substring(scriptName.lastIndexOf('/')+1)
    // if(scriptName.includes("?"))
    //     scriptName = scriptName.split("?")[0];
    // if(scriptName.includes(".php"))
    //     scriptName = scriptName.split(".php")[0];

    scriptName = window.location.pathname;
    $(".main-sidebar a[href*='"+scriptName+"']").addClass('active')
    $(".main-sidebar a[href*='"+scriptName+"']").parent('li').parent('ul').parent('li').addClass('menu-open')
    $(".main-sidebar a[href*='"+scriptName+"']").parent('li').parent('ul').parent('li').children('a').addClass('active')


    setPreferences();

    //SIDEBAR
    if(localStorage.getItem('admin.sidebar_collapse') == 'true') {
        $('body').addClass('sidebar-collapse');
    }

    // if(localStorage.getItem('admin.sidebar_nav_open'))
    //     $(localStorage.getItem('admin.sidebar_nav_open')).addClass('menu-open');
    //
    // if(localStorage.getItem('admin.sidebar_active'))
    //     $(localStorage.getItem('admin.sidebar_active')).addClass('active');


    setPreferences();

    $("body").fadeIn();

    if($(".select2").length)
        $(".select2").select2();

    if($(".bs-switch"))
        $(".bs-switch").bootstrapSwitch();

    //SELECT TAB
    var params = new URL(window.location.href).searchParams;
    for(const [ key, value ] of params){
        if(key.includes('tab'))
            $("#"+key).click();
    }

    GetNotifications('admin');



});


$( window ).on( "resize", function() {
    if($(".select2").length)
        $(".select2").select2();
});