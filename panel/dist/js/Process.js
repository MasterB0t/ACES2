const PROCESS = {
    url : 'ajax/pProcess.php',
    appendTo : '.row.process',
    icon : ' fa-spin fa-gear ',
    divType : 'box',
    boxClass : 'bg-info',
    processName : '',
    processToGet : [],
    ids : [],

    stop : function(process_id, process_name,  url = '',  element) {

        url = url === '' ? 'ajax/pProcess.php' : url;

        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: { action : 'kill_process', 'process_id' : process_id, process_name : process_name },
            success: function (resp) {
                document.getElementById(element).remove();
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
    },
    get: function( OBJECT ) {

        OBJECT.url = typeof OBJECT.url === 'undefined' ? PROCESS.url : OBJECT.url;
        OBJECT.appenTo = typeof OBJECT.appenTo === 'undefined' ? PROCESS.appendTo : OBJECT.appendTo;
        OBJECT.icon = typeof OBJECT.icon === 'undefined' ? PROCESS.icon : OBJECT.icon;
        OBJECT.divType = typeof OBJECT.divType === 'undefined' ? PROCESS.divType : OBJECT.divType;
        OBJECT.boxClass = typeof OBJECT.boxClass === 'undefined' ? PROCESS.boxClass : OBJECT.boxClass;
        OBJECT.ids  = typeof OBJECT.ids === 'undefined' ? [] : OBJECT.ids;

        var process_name = OBJECT.processToGet === 'undefined' ? [] : OBJECT.processToGet;
        url_params = typeof process_name === 'object' ? process_name.join(',') : process_name;

        $.ajax({
            url: OBJECT.url+"?action=get_process&process_name="+url_params,
            type: 'GET',
            dataType: 'json',
            success: function (resp) {

                var ids = [];
                var data = [];

                for( const[key , arr ] of Object.entries(resp.data)) {
                    for(x = 0; x < arr.length; x++) {
                        data = arr[x];

                        var element_id = data.type + '-' + data.id
                        var progress_id = data.type + '-progress-' + data.id;
                        ids.push(element_id);

                        if(document.getElementById(element_id) == null ) {
                            htm = '<div id="'+element_id+'" class="info-box '+OBJECT.boxClass+' ">' +
                                '<span class="info-box-icon">' +
                                '<i class="fa '+OBJECT.icon+'"></i>' +
                                '</span>' +
                                '<div class="info-box-content">' +
                                '<span style="font-size:13pt" class="info-box-text text-bold"> '+data.description+' </span>' +
                                '<span class="info-box-number"></span>' +
                                '<div class="progress"> ' +
                                '<div id="'+progress_id+'" class="progress-bar" style="width:'+data.progress+'%"></div>' +
                                '</div>' +
                                '<span class="progress-description ">' +
                                "<a class=\'text-danger text-bold\' onClick=\" setTimeout( PROCESS.stop , 1, "+data.id+", '"+data.type+"', '"+OBJECT.url+"', '"+element_id+"') \" href='#!'> Stop </a>" +
                                '</span>' +
                                '</div>' +
                                '</div>';


                            $( OBJECT.appendTo ).append(htm)

                        } else {
                            if(document.getElementById(progress_id) != null )
                                document.getElementById(progress_id).style.width =  data.progress + '%';
                        }

                    }
                }

                var toHide = OBJECT.ids.filter( function( el ) {
                    return ids.indexOf( el ) < 0 ;
                });
                for(var y = 0; y < toHide.length; y++ ) {
                    if(document.getElementById(toHide[y]) != null )
                        document.getElementById(toHide[y]).remove();
                }

                OBJECT.ids = ids;

                setTimeout( () => {
                    PROCESS.get(OBJECT)
                }, 5000 )

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

}
