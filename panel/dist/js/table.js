var _DATANAME = window.location.href + "_datatable" ;
var TABLES;
var _REALTIMELOCKS = [];
var _REALTIMEINTERVAL = [];
var _REALTIME = [];

Object.assign(DataTable.defaults, {
    layout: {
        top2Start: 'buttons',
        top2End: {  pageLength: {
                menu: [ 10, 25, 50, 100, 1000 ]
            }},
        topStart: 'info',
        topEnd: 'search',
        bottomStart: null,
        bottomEnd: 'pageLength',
        bottom2Start: 'info',
        bottom2End: 'paging'
    },
    responsive: true,
    lengthChange: true,
    autoWidth: false,
    info: true,
    serverSide: true,
    stateSave: true,
    ordering: true,
    searching: true,
    drawCallback: function (settings) {
        var json = this.api().ajax.json();
        if ( json.not_logged == 1 ) window.location.reload();
    },
    order: [[0, 'desc']],
});

function RealTimeTable(table_id, active) {

    table = TABLES.tables(table_id);

    localStorage.setItem(_DATANAME+table_id, active);

    if(!active) {
        _REALTIMELOCKS[table_id] = false;
        clearInterval(_REALTIMEINTERVAL[table_id]);
        return;
    }

    if(_REALTIMELOCKS[table_id])
        return;

    _REALTIMELOCKS[table_id] = true;

    _REALTIMEINTERVAL[table_id] = setInterval(function() {
        table.ajax.reload(function(){
            _REALTIMELOCKS[table_id] = false
        },false)
    },5000);

}


$("[datatable-realtime] li a").click(function () {
    table_id = $(this).parent().parent().attr('datatable-realtime');
    _REALTIME[table_id] = !_REALTIME[table_id];
    RealTimeTable(table_id,_REALTIME[table_id])
});

$(document).ready(function() {

    $("#toggleFilter").click(function() {
        $(".divFilters").toggle();
    });

    $("[datatable-clear-filter]").click(function() {

        table = TABLES.tables($(this).attr('datatable-clear-filter'));

        $(this).find("select").val('')
        $(this).find("input").val('')
        $(this).find("select").change();

        $("[datatable-filter] input").val('');
        $("[datatable-filter] select").val('');
        $("[datatable-filter] select").change();
        $(".divFilters").fadeOut();

        table.page(0);
        table.ajax.reload(null,false);

    });

    //FILTERS
    $("[datatable-filter] select, [datatable-filter] input").change(function() {

        args = "";
        var filters = {};
        $('[datatable-filter] select, [datatable-filter] input').each(function(){
            if($(this).val()) {
                name = $(this).attr('name');
                args += "&filter_" + name + "=" + $(this).val()
                filters[name] = $(this).val();
            }
        });

        var table = TABLES.tables($(this).attr('datatable-filter'));

        url = table.ajax.url().split("?")[0] + "?" + args;
        table.ajax.url(url);
        table.ajax.reload(null,false);
        table.page(0);

        localStorage.setItem(_DATANAME+"_filters", JSON.stringify(filters) );
        window.history.pushState('', '', '?'+args);

    });


    $("[datatable-realtime]").each(function(){
        table_id = $(this).attr('datatable-realtime');
        if(localStorage.getItem(_DATANAME+table_id) === "true") {

            _REALTIME[table_id] = true;
            RealTimeTable(table_id,true);
            $(this).find("li:nth-child(2) a").addClass('active');
            $(this).find("li:nth-child(1) a").removeClass('active');
        }

    });


    var _gf = 0;
    var params = new URL(window.location.href).searchParams;
    if(params.size > 0 ) {
        for(const [ key, value ] of params){
            if(key.includes('filter_')) {
                objname = key.split('filter_')[1];

                type = '';
                if($("[datatable-filter] select[name='"+objname+"']").length > 0 )
                    type = 'select';
                else if($("[datatable-filter] input[name='"+objname+"']").length > 0)
                    type = 'input';

                if(type) {
                    _gf++;
                    $("[datatable-filter] "+type+"[name='"+objname+"']").val(value)
                    $("[datatable-filter] "+type+"[name='"+objname+"']").change()
                    if($("[datatable-filter] "+type+"[name='"+objname+"']").parent().parent().hasClass('d-none'))
                       $("[datatable-filter] "+type+"[name='"+objname+"']").parent().parent().removeClass('d-none');
                }

            }
        }

        if(_gf>0)
            $(".divFilters").show();


    }

    if( _gf === 0 ) {

        var _filters = JSON.parse(localStorage.getItem(_DATANAME+"_filters"));

        if( _filters !== null )
        Object.keys(_filters).forEach(function(key) {

            if($("[datatable-filter] select[name='"+key+"']").length > 0 ) {
                $("[datatable-filter] select[name='" + key + "']").val(_filters[key])
                $("[datatable-filter] select[name='" + key + "']").change();
                $(".divFilters").show();
            }

        });
    }

})

