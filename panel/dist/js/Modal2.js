const Modal2 = {

    modalId : 'Modal',
    url : 'modal/',
    appendToElement : "section.content",
    backdrop : '',
    size : 'medium',

    get : function(OBJECT) {

        OBJECT.url = typeof OBJECT.url === 'undefined' ? Modal2.url : OBJECT.url;
        OBJECT.modalId = typeof OBJECT.modalId === 'undefined' ? Modal2.modalId : OBJECT.modalId;
        OBJECT.appendToElement = typeof OBJECT.appendToElement === 'undefined' ? Modal2.appendToElement : OBJECT.appendToElement;
        OBJECT.size = typeof OBJECT.size === 'undefined' ? Modal2.size : OBJECT.size;

        var backdrop = OBJECT.appendToElement == 'statis' ? backdrop = 'data-backdrop="static"' : '';

        if($( document ).width() < 1000 ) {
            width = $( document ).width() - ($( document ).width() * 0.10);
        }

        var width = '600px';
        var cwidth = 'modal-xl';
        switch(OBJECT.size) {
            case 'small':
                width = '300px'; cwidth = 'modal-sm';
                break;
            case 'large' :
                cwidth = 'modal-lg';
                width = '900px'; break;
            case 'xlarge' :
                cwidth = 'modal-xl';
                width = '1200px';
            case 'full':
                width = $( document ).width() - ($( document ).width() * 0.05);
                width = '98%';
                break;

        }


        $('.cmodal').each(function(i, obj) {

            if($(obj).hasClass('in'))
                $(obj).modal('hide');
            $(obj).remove();

        });
        $(".modal-backdrop").remove();

        //REMOVING OLD MODALS
        Modal2.modalId = OBJECT.modalId + Date.now();
        if ( $("#" + Modal2.modalId ).length )
            $("#" + Modal2.modalId ).remove();

        $("section.content").prepend(
            ' <div id="' + Modal2.modalId + '" class="modal fade cmodal" '+backdrop+'>' +
            '                        <div style="width:'+width+'" class="modal-dialog '+cwidth+'">' +
            '                            <div class="modal-content">' +
            '                            </div>' +
            '                        </div>' +
            '                    </div>');


        $.get( OBJECT.url , function( data ) {

            $("#"+Modal2.modalId +" .modal-dialog .modal-content").html(data);
            $("#"+Modal2.modalId).modal('show');
            if(typeof successCall == 'function' ) successCall();

        }).fail( function(x){
            if(x.status == 401 ) location.reload();
            else if( x.status == 403 ) alert("You don't have permissions to perform this action.");
            else if ( x.getResponseHeader('Error-Msg') != null ) alert( x.getResponseHeader('Error-Msg') );
            else alert("System Error.");
            $(modal_id).modal('hide');
        });


    },

    hide : function () {
        $("#"+this.modalId).modal('hide');
    }

}